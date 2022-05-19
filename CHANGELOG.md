# Changelog

## [Unreleased]

### Added

- Fichier `CHANGELOG.md`

### Fixed

- #4855 Ne pas nécessiter la présence d’un htaccess pour traiter l’url d’api de statistiques


## [3.0.3] - 2022-03-25

### Changed

- Nécessite SPIP 4.1.0 minimum


## [3.0.2] - 2022-03-05

### Fixed

- Éviter de passer null à number_format ou round


## [3.0.1] - 2022-02-17

### Changed

- Mise à jour des chaînes de langues depuis trad.spip.net


## [3.0.0] - 2022-02-08

### Changed

- Nécessite SPIP 4.1.0-alpha minimum
- Nécessite PHP 7.4 minimum
- Utiliser l'API `transmettre.api` pour les acces aux stats json et csv
- Utiliser les nouvelles fonctions `generer_objet_*`
- Up D3.js en version 7.3.0
- Up Luxon.js en version 2.3.0
- Up d3-time-format en version 4.1.0

### Fixed

- Dans D3.js, utiliser la locale ar-SY pour la langue arabe
