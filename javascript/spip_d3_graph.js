
class Spip_d3_graph {
	/**
	 * @param string id Identifiant sélecteur du conteneur
	 * @param {*} options Options
	 *   - locale : locale, tel que "fr-FR"
	 *   - language : (à défaut de locale) language, tel que "fr"
	 *   - d3_directory : chemin vers le répertoire de d3...
	 */
	constructor(id, options = {}) {
		this.id = id;
		this.container = d3.select(this.id);
		this.inner = this.container.select('.spip_d3_graph_inner');

		d3.spip = d3.spip || {};
		d3.spip.locale = d3.spip.locale || {
			default: "en_EN",
			loaded: "en_EN",
			desired: "",
			data: {},
		}
		if (typeof options.locale !== 'undefined') {
			this.set_desired_locale(options.locale);
		} else if (typeof options.language !== 'undefined') {
			this.set_desired_locale(this.language_to_locale(options.language));
		}
		this.d3_directory = options.d3_directory || "";
	}

	set_desired_locale(locale) {
		locale = locale.replace('_', '-');
		d3.spip.locale.desired = locale;
	}

	// depuis un nom de langue, trouver un code de langue adapté…
	language_to_locale(language) {
		language = language.toLowerCase();
		const l2l = {
			'ar': 'ar-SY',
			'ca': 'ca-ES',
			'cs': 'cs-CZ',
			'da': 'da-DK',
			//'de': 'de-CH',
			'de': 'de-DE',
			//'en': 'en-CA',
			'en': 'en-GB',
			//'en': 'en-US',
			'es': 'es-ES',
			//'es': 'es-MX',
			'fa': 'fa-IR',
			'fi': 'fi-FI',
			//'fr': 'fr-CA',
			'fr': 'fr-FR',
			'he': 'he-IL',
			'hu': 'hu-HU',
			'it': 'it-IT',
			'ja': 'ja-JP',
			'ko': 'ko-KR',
			'mk': 'mk-MK',
			'nb': 'nb-NO',
			'nl': 'nl-NL',
			'pl': 'pl-PL',
			'pt': 'pt-BR',
			'ru': 'ru-RU',
			'sv': 'sv-SE',
			'tr': 'tr-TR',
			'uk': 'uk-UA',
			'zh': 'zh-CN',
			//'zh': 'zh-TW'
		};
		if (language in l2l) {
			return l2l[language];
		}
		return language + '-' + language.toUpperCase();
	}

	/**
	 * Localise les dates
	 * 
	 * @param {*} data Data à retourner ensuite
	 * @param {*} desired_locale Locale désirée (sinon utilise celle déjà demandée via constructeur ou set_desired_locale)
	 * @returns 
	 */
	async localize_d3_time(data = {}, desired_locale = null) {
		// bazar pour avoir la localisation avant de tracer le graphique...
		// il faut le faire en asynchrone...
		if (desired_locale !== null) {
			this.set_desired_locale(desired_locale);
		}
		const locale = d3.spip.locale;
		if (locale.desired && locale.loaded !== locale.desired) {
			if (locale.desired in locale.data) {
				d3.timeFormatDefaultLocale(locale.data[locale.desired]);
			} else {
				const localization_file = this.d3_directory + "/time-format/locale/" + locale.desired + ".json";
				await d3
					.json(localization_file)
					.then(definition => {
						d3.spip.locale.loaded = locale.desired;
						d3.spip.locale.data[locale.desired] = definition;
						return d3.timeFormatDefaultLocale(definition)
					})
					.catch(error => console.warn("d3 language file for " + locale.desired + " not loaded", error));
	
			}
		}
		return data;
	}

	loading_start() {
		this.container.node().classList.add("spip_d3_graph--loading");
	}

	loading_end() {
		this.container.node().classList.remove("spip_d3_graph--loading");
	}

	set_dataLoader(callback) {
		this.jsonLoaderCallback = callback;
	}

	updateJson() {
		this.loading_start();
		const me = this;
		d3
			.json(this.get_jsonUrl())
			.then(this.jsonLoaderCallback)
			.then(data => {
				this.loading_end();
				return data;
			})
			.catch(function(error) {
				console.error(error);
				me.loading_end();
				me.error(error);
			})
	}


	get_jsonUrl() {
		return this.container.attr("data-json");
	}

	error(error) {
		this.container.append("div").attr("class", "error").text(error);
	}

	nextDate(date, unit, add) {
		const current = luxon.DateTime.fromISO(date);
		const next = current.plus({[unit]: add});
		if (unit === 'day') {
			return next.toISODate();
		} else if (unit === 'week') {
			return next.toFormat("kkkk-'W'WW");
		} else if (unit === 'month') {
			return next.toFormat('yyyy-LL');
		} else if (unit === 'year') {
			return next.toFormat('yyyy');
		} else {
			throw "invalid unit in nextDate().";
		}
	}

	/** 
	 * Remplit les dates manquantes 
	 * 
	 * onEmpty est une fonction qui reçoit la date manquante,
	 * et doit retourner un objet décrivant l'élément vide
	*/
	fillInDates(meta, data, onEmpty) {
		const currentDates = {};
		const minDate = meta.start_date;
		let currentDate = minDate;
		const maxDate = meta.end_date;

		data.forEach(d => { 
			currentDates[d.date] = d; 
		});

		// loop data and fill in missing dates
		const filledInDates = [];
		while (currentDate <= maxDate) {
			if (currentDates[currentDate]) {
				filledInDates.push(currentDates[currentDate]);
			} else {
				filledInDates.push(onEmpty(currentDate));  
			}
			currentDate = this.nextDate(currentDate, meta.unite, 1);
		}
		// we push next date for xaxis to show last histogram bar.
		filledInDates.push(onEmpty(currentDate));  

		return filledInDates;
	}

	prepare_columns(data) {
		const columns = [];
		for (const [key, value] of Object.entries(data.meta.columns)) {
			columns.push({key: value, label: data.meta.translations[value]});
		}
		return columns;
	}

	update_table(data, ignore_last_line = false) {
		if (this.inner.select('table').empty()) {
			this.prepare_table();
		}
		const table = this.inner.select('table');

		const caption = table.select('caption');
		if (this.container.attr("data-title")) {
			caption.text(this.container.attr("data-title")).style('display', 'table-caption');
		} else {
			caption.text("").style('display', 'none');
		}

		const columns = this.prepare_columns(data);
		table.select('thead tr')
			.selectAll('th')
			.data(columns)
			.join('th')
			.text(column => column.label);
	
		let _data = data.data;
		if (ignore_last_line) {
			_data = _data.slice(0, -1)
		}
		table.select('tbody')
			.selectAll('tr')
			.data(_data)
			.join('tr')
			.selectAll('td')
			.data(d => {
				return columns.map(column => {
					return { column: column, value: d[column.key] }
				});
			})
			.join('td')
			.text(d => d.value)
			.attr("data-label", d => d.column.label);;
	}

	prepare_table(visible = false) {
		const table = this.inner
			.append('table')
			.attr("class", "spip_d3_table spip_table--responsive");
		if (!visible) {
			table.style("display", "none");
		}

		table.append('caption');
		table.append('thead').append('tr');
		table.append('tbody');

		return table;
	}
}

