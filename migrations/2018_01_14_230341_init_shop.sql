DROP TABLE IF EXISTS `ss_user_service_shopsms_license`;
CREATE TABLE `ss_user_service_shopsms_license` (
  `us_id` int(11) NOT NULL,
  `service_id` varchar(16) NOT NULL,
  `identifier` varchar(40) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cost_daily` int(11) NOT NULL,
  `platform_amxmodx` tinyint(1) NOT NULL,
  `platform_sourcemod` tinyint(1) NOT NULL,
  `subdomain` varchar(255) NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `ss_user_service_shopsms_license`
  ADD PRIMARY KEY (`us_id`),
  ADD UNIQUE KEY `identifier_unique` (`identifier`),
  ADD KEY `service_id` (`service_id`);

ALTER TABLE `ss_user_service_shopsms_license`
  ADD CONSTRAINT `ss_user_service_shopsms_license_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

DELETE FROM `ss_services`;
INSERT INTO `ss_services` (`id`, `name`, `short_description`, `description`, `types`, `tag`, `module`, `groups`, `flags`, `order`, `data`) VALUES
('charge_wallet', 'Doładowanie Portfela', '', '<strong>Doładowanie Portfela</strong> pozwala zwiększyć stan wirtualnej gotówki w celu dokonywania przyszłych zakupów.', 0, 'PLN', 'charge_wallet', '', '', 0, ''),
('ss_license', 'Licencja Sklep SMS', '', '<strong>Licencja Sklep SMS</strong> pozwala na korzystanie ze skryptu na swojej stronie oraz swoich serwerach.', 0, 'dni', 'shopsms_license', '', '', 1, ''),
('ss_license_edit', 'Edycja licencji', '', '', 0, '', 'shopsms_license_edit', '', '', 0, ''),
('ss_license_plong', 'Przedłużenie Licencji', '', '<strong>Przedłużenie Licencji</strong> pozwala na przedłużenie licencji.', 0, 'dni', 'shopsms_license_prolong', '', '', 2, '');
