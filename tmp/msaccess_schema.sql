-- ----------------------------------------------------------
-- MDB Tools - A library for reading MS Access database files
-- Copyright (C) 2000-2011 Brian Bruns and others.
-- Files in libmdb are licensed under LGPL and the utilities under
-- the GPL, see COPYING.LIB and COPYING files respectively.
-- Check out http://mdbtools.sourceforge.net
-- ----------------------------------------------------------

-- That file uses encoding UTF-8

CREATE TABLE `00_EDITORIA`
 (
	`CODI`			varchar (3), 
	`NOM`			varchar (255), 
	`DATA_ENTR`			datetime, 
	`ADREÇA`			varchar (255), 
	`MUNICIPI`			varchar (255), 
	`CODI_POST`			varchar (5), 
	`TELEFON`			varchar (10), 
	`FAX`			varchar (10), 
	`EMAIL`			varchar (255), 
	`INTERNET`			varchar (255), 
	`CONTACTE`			varchar (255), 
	`DARRER_CAT`			datetime, 
	`OBSERVACIO`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `00_EDITORIA` ADD INDEX `CODI` (`CODI`);
ALTER TABLE `00_EDITORIA` ADD INDEX `NOM` (`NOM`);

CREATE TABLE `00_EQUIVALENTS`
 (
	`CODI`			varchar (255) NOT NULL, 
	`IDIOMA`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `00_EQUIVALENTS` ADD PRIMARY KEY (`CODI`);

CREATE TABLE `00_FONTS`
 (
	`Comptador`			int not null auto_increment unique, 
	`Identificador`			varchar (255), 
	`CODI_RML`			varchar (255), 
	`Autor`			varchar (255), 
	`Any`			varchar (10), 
	`Títol`			varchar (255), 
	`ISBN`			varchar (50), 
	`Codi_edit`			varchar (3), 
	`Editorial`			varchar (255), 
	`Municipi`			varchar (255), 
	`Edició`			varchar (25), 
	`Any_edició`			int, 
	`Collecció`			varchar (255), 
	`Núm_collecció`			varchar (255), 
	`Pàgines`			int, 
	`Idioma`			varchar (255), 
	`Varietat_dialectal`			varchar (255), 
	`Registres`			int, 
	`Preu`			float, 
	`Data_compra`			date, 
	`Lloc_compra`			varchar (255), 
	`Imatge`			varchar (255), 
	`URL`			varchar (255), 
	`Observacions`			text
);

-- CREATE INDEXES ...
ALTER TABLE `00_FONTS` ADD INDEX `Identificador` (`Identificador`);
ALTER TABLE `00_FONTS` ADD INDEX `Idioma` (`Idioma`);
ALTER TABLE `00_FONTS` ADD INDEX `Núm_collecció` (`Núm_collecció`);
ALTER TABLE `00_FONTS` ADD PRIMARY KEY (`Comptador`);

CREATE TABLE `00_IMATGES`
 (
	`Comptador`			int not null auto_increment unique, 
	`Identificador`			varchar (255), 
	`TIPUS`			varchar (1), 
	`MODISME`			varchar (255), 
	`PAREMIOTIPUS`			varchar (255), 
	`IDIOMA`			varchar (255), 
	`EQUIVALENT`			varchar (255), 
	`LLOC`			varchar (255), 
	`DESCRIPCIO`			varchar (255), 
	`AUTOR`			varchar (255), 
	`ANY`			double, 
	`EDITORIAL`			varchar (3), 
	`DIARI`			varchar (255), 
	`ARTICLE`			varchar (200), 
	`PAGINA`			varchar (10), 
	`URL_ENLLAÇ`			varchar (255), 
	`TIPUS_IMATGE`			varchar (255), 
	`URL_IMATGE`			varchar (255), 
	`OBSERVACIONS`			varchar (255), 
	`DATA`			date
);

-- CREATE INDEXES ...
ALTER TABLE `00_IMATGES` ADD INDEX `Identificador` (`Identificador`);
ALTER TABLE `00_IMATGES` ADD INDEX `IDIOMA` (`IDIOMA`);
ALTER TABLE `00_IMATGES` ADD PRIMARY KEY (`Comptador`);

CREATE TABLE `00_MATRIU2019`
 (
	`Id`			int not null auto_increment unique, 
	`TIPUS`			varchar (1), 
	`MODISME`			varchar (255), 
	`PAREMIOTIPUS`			varchar (255), 
	`SINONIM`			varchar (255), 
	`IDIOMA`			varchar (255), 
	`EQUIVALENT`			varchar (255), 
	`LLOC`			varchar (255), 
	`AUTORIA`			varchar (255), 
	`FONT`			varchar (255), 
	`EXPLICACIO`			varchar (255), 
	`EXPLICACIO2`			varchar (255), 
	`EXEMPLES`			varchar (255), 
	`AUTOR`			varchar (255), 
	`ANY`			double, 
	`EDITORIAL`			varchar (3), 
	`ID_FONT`			varchar (255), 
	`DIARI`			varchar (255), 
	`ARTICLE`			varchar (200), 
	`PAGINA`			varchar (10), 
	`NUM_ORDRE`			varchar (255), 
	`DATA`			date
);

-- CREATE INDEXES ...
ALTER TABLE `00_MATRIU2019` ADD INDEX `ID_FONT` (`ID_FONT`);
ALTER TABLE `00_MATRIU2019` ADD INDEX `IDIOMA` (`IDIOMA`);
ALTER TABLE `00_MATRIU2019` ADD PRIMARY KEY (`Id`);

CREATE TABLE `00_PAREMIOTIPUS`
 (
	`Id`			int not null auto_increment unique, 
	`TIPUS`			varchar (1), 
	`MODISME`			varchar (255), 
	`PAREMIOTIPUS`			varchar (255), 
	`SINONIM`			varchar (255), 
	`IDIOMA`			varchar (255), 
	`EQUIVALENT`			varchar (255), 
	`LLOC`			varchar (255), 
	`AUTORIA`			varchar (255), 
	`FONT`			varchar (255), 
	`EXPLICACIO`			varchar (255), 
	`EXPLICACIO2`			varchar (255), 
	`EXEMPLES`			varchar (255), 
	`AUTOR`			varchar (255), 
	`ANY`			double, 
	`EDITORIAL`			varchar (3), 
	`ID_FONT`			varchar (255), 
	`DIARI`			varchar (255), 
	`ARTICLE`			varchar (200), 
	`PAGINA`			varchar (10), 
	`NUM_ORDRE`			varchar (255), 
	`DATA`			date
);

-- CREATE INDEXES ...
ALTER TABLE `00_PAREMIOTIPUS` ADD INDEX `ID_FONT` (`ID_FONT`);
ALTER TABLE `00_PAREMIOTIPUS` ADD INDEX `IDIOMA` (`IDIOMA`);

CREATE TABLE `RML`
 (
	`NUM_ORDRE`			int not null auto_increment unique, 
	`COMPLET`			varchar (255), 
	`TIPUS`			varchar (255), 
	`MODISME`			varchar (255), 
	`F_MODISME`			varchar (255), 
	`PAREMIOTIPUS`			varchar (255), 
	`DEFINICIÓ`			varchar (255), 
	`F_DEFINICIÓ`			varchar (255), 
	`SINÒNIMS`			varchar (255), 
	`CODI_CASTELLÀ`			varchar (255), 
	`EQ_CASTELLÀ`			varchar (255), 
	`F_CASTELLÀ`			varchar (255), 
	`CODI_ANGLÈS`			varchar (255), 
	`EQ_ANGLÈS`			varchar (255), 
	`F_ANGLÈS`			varchar (255), 
	`CODI_LLATÍ`			varchar (255), 
	`EQ_LLATÍ`			varchar (255), 
	`F_LLATÍ`			varchar (255), 
	`CODI_FRANCÈS`			varchar (255), 
	`EQ_FRANCÈS`			varchar (255), 
	`F_FRANCÈS`			varchar (255), 
	`CODI_ITALIÀ`			varchar (255), 
	`EQ_ITALIÀ`			varchar (255), 
	`F_ITALIÀ`			varchar (255), 
	`CODI_PORTUGUÈS`			varchar (255), 
	`EQ_PORTUGUÈS`			varchar (255), 
	`F_PORTUGUÈS`			varchar (255), 
	`CODI_GALLEC`			varchar (255), 
	`EQ_GALLEC`			varchar (255), 
	`F_GALLEC`			varchar (255), 
	`CODI_OCCITÀ`			varchar (255), 
	`EQ_OCCITÀ`			varchar (255), 
	`F_OCCITÀ`			varchar (255), 
	`CODI_ASTURIÀ`			varchar (255), 
	`EQ_ASTURIÀ`			varchar (255), 
	`F_ASTURIÀ`			varchar (255), 
	`CODI_ROMANÈS`			varchar (255), 
	`EQ_ROMANÈS`			varchar (255), 
	`F_ROMANÈS`			varchar (255), 
	`CODI_ARAGONÈS`			varchar (255), 
	`EQ_ARAGONÈS`			varchar (255), 
	`F_ARAGONÈS`			varchar (255), 
	`CODI_ESPERANTO`			varchar (255), 
	`EQ_ESPERANTO`			varchar (255), 
	`F_ESPERANTO`			varchar (255), 
	`CODI_EUSKARA`			varchar (255), 
	`EQ_EUSKARA`			varchar (255), 
	`F_EUSKARA`			varchar (255), 
	`OBSERVACIONS`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `RML` ADD PRIMARY KEY (`NUM_ORDRE`);

CREATE TABLE `00_OBRESVPR`
 (
	`Comptador`			int NOT NULL, 
	`Identificador`			varchar (255), 
	`Autor`			varchar (255), 
	`Any`			varchar (10), 
	`Títol`			varchar (255), 
	`ISBN`			varchar (50), 
	`Codi_edit`			varchar (3), 
	`Editorial`			varchar (255), 
	`Municipi`			varchar (255), 
	`Edició`			varchar (25), 
	`Any_edició`			int, 
	`Collecció`			varchar (255), 
	`Núm_collecció`			varchar (255), 
	`Pàgines`			int, 
	`Idioma`			varchar (255), 
	`Preu`			float, 
	`Imatge`			varchar (255), 
	`URL`			varchar (255)
);

-- CREATE INDEXES ...
ALTER TABLE `00_OBRESVPR` ADD INDEX `Identificador` (`Identificador`);
ALTER TABLE `00_OBRESVPR` ADD INDEX `Idioma` (`Idioma`);
ALTER TABLE `00_OBRESVPR` ADD INDEX `Núm_collecció` (`Núm_collecció`);
ALTER TABLE `00_OBRESVPR` ADD PRIMARY KEY (`Comptador`);


-- CREATE Relationships ...
ALTER TABLE `MSysNavPaneGroups` ADD CONSTRAINT `MSysNavPaneGroups_GroupCategoryID_fk` FOREIGN KEY (`GroupCategoryID`) REFERENCES `MSysNavPaneGroupCategories`(`Id`) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `MSysNavPaneGroupToObjects` ADD CONSTRAINT `MSysNavPaneGroupToObjects_GroupID_fk` FOREIGN KEY (`GroupID`) REFERENCES `MSysNavPaneGroups`(`Id`) ON UPDATE CASCADE ON DELETE CASCADE;
