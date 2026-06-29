-- ============================================================
-- SBLRULSEED  —  seed data for LSCDEVLIBP.SBLRULEST
-- Per-coin "turns red" validation rules.
-- Run once after creating the table (ACS Run SQL Scripts → Run All,
-- or copy to a QSQLSRC member and RUNSQLSTM ... COMMIT(*NONE)).
-- Re-running will hit the UNIQUE constraint; clear the table first.
-- ============================================================

INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('name', 'action', 'Title is over 70 characters (Amazon/eBay limit)', '[{"field":"name","op":"len_gt","value":70}]', 0);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('price', 'error', 'Price must be greater than cost', '[{"field":"price","op":"num"},{"field":"cost","op":"num"},{"field":"price","op":"le_field","value":"cost"}]', 1);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('price', 'action', 'Add a price before uploading', '[{"field":"price","op":"blank"}]', 2);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('certification_number', 'error', 'Certification number is required for graded coins', '[{"field":"certification","op":"in","value":["PCGS","NGC","ANACS","PCGS & CAC","NGC & CAC"]},{"field":"single_coin_or_set","op":"ne","value":"Set"},{"field":"certification_number","op":"blank"}]', 3);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('title_suffix', 'action', 'Sets and commemoratives usually need a title suffix', '[{"field":"title_suffix","op":"blank"},{"field":"category_name","op":"in","value":["Proof Set","Mint Set","Modern Silver\/Clad Commemorative","Modern Gold Commemorative"]}]', 4);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('coin_variety_1', 'action', 'Commemoratives and key dates usually need a variety', '[{"field":"coin_variety_1","op":"blank"},{"field":"coin_variety_2","op":"blank"},{"field":"coin_type","op":"eq","value":"Commemorative"}]', 5);
INSERT INTO LSCDEVLIBP.SBLRULEST (field_name, rule_type, message, condition_json, sort_order) VALUES ('modification_description', 'action', 'Describe the modification when the item is modified', '[{"field":"modified_item","op":"eq","value":"Yes"},{"field":"modification_description","op":"blank"}]', 6);
