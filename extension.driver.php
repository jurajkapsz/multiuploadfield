<?php

class extension_multiuploadfield extends Extension {
    public function install()
    {
        return Symphony::Database()->query("
            CREATE TABLE `tbl_fields_multiupload` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` int(11) unsigned NOT NULL,
              `destination` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `validator` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function update($previousVersion = null)
    {
        // Prior version 1.6.3:
        // Auto-update meta data of existing SVG entries
        if (version_compare($previousVersion, '1.6.3', '<')) {
            $multiupload_fields = FieldManager::fetch(null, null, 'ASC', 'id', 'multiupload');

            foreach ($multiupload_fields as $field) {
                $table_id = $field->get('id');
                $file_path = DOCROOT . $field->get('destination');
                $svg_files = Symphony::Database()->fetch("SELECT `id`, `file`, `mimetype` FROM `tbl_entries_data_$table_id` WHERE `mimetype` LIKE '%image/svg%'");

                if (!empty($svg_files)) {
                    foreach ($svg_files as $svg) {
                        $entry_id = intval($svg['id']);
                        $file = $file_path . '/' . $svg['file'];
                        $mimetype = $svg['mimetype'];
                        $meta = serialize(FieldMultiUpload::getMetaInfo($file, $mimetype));

                        Symphony::Database()->query("UPDATE `tbl_entries_data_$table_id` SET `meta` = '" . $meta . "' WHERE `id` = $entry_id");
                    }
                }
            }
        }
    }

    public function uninstall()
    {
        Symphony::Database()->query("DROP TABLE `tbl_fields_multiupload`");
    }
}
