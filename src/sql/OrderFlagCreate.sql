CREATE TABLE IF NOT EXISTS `{$pfx}order_flag` (
  `id_order` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_flag` int(11) NOT NULL,
  `id_employee` int(11) DEFAULT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  PRIMARY KEY (`id_order`),
  KEY `id_order_flag` (`id_order_flag`),
  KEY `id_employee` (`id_employee`)
) ENGINE={$engine};