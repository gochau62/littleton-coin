--  Adds the LCC link to SBLPRODUCT so a saved row remembers which item it came
--  from, and the same coin in another condition can be found and reused.
--  Run in ACS "Run SQL Scripts".  Existing rows are untouched (both come back NULL).

ALTER TABLE LSCDEVLIBP.SBLPRODUCT ADD COLUMN LCC_SKU  CHAR(10);
ALTER TABLE LSCDEVLIBP.SBLPRODUCT ADD COLUMN LCC_ROOT CHAR(10);

--  Siblings are looked up by root, so give it an index.
CREATE INDEX LSCDEVLIBP.SBLPRODLCC ON LSCDEVLIBP.SBLPRODUCT (LCC_ROOT);


--  ---------------------------------------------------------------------
--  TO REVERT: run these three instead.  The loader checks for the columns
--  and simply stops reusing siblings once they are gone - no code change
--  needed, nothing else on the table is affected.
--  ---------------------------------------------------------------------
--  DROP INDEX LSCDEVLIBP.SBLPRODLCC;
--  ALTER TABLE LSCDEVLIBP.SBLPRODUCT DROP COLUMN LCC_ROOT;
--  ALTER TABLE LSCDEVLIBP.SBLPRODUCT DROP COLUMN LCC_SKU;
