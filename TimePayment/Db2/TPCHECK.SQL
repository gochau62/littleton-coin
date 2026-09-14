/*-------------------------------------------------------------------*/
/*                                                                   */
/* Program Name      - TPCHECK                                       */
/*                                                                   */
/* Program Narrative - Diagnostic reads for the Holiday Gift catalog */
/*                     upload, where skus 1638.60 and 1738.40 were   */
/*                     rejected as not on the Item Master.  Proves   */
/*                     what is on file, what the real sku spelling   */
/*                     is, and how many items the same Excel number  */
/*                     formatting could affect.  Reads only.         */
/*                                                                   */
/* Author            - G Chau                                        */
/*                     Littleton Coin Company                        */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 09/14/2026                                    */
/* Run Instructions  - ACS Run SQL Scripts, one statement at a time  */
/*********************************************************************/
/*                                                                   */
/* __________________________________________________________________*/
/* |                  Maintenance History                           |*/
/* |                                                                |*/
/* | Programmer -                Date -                             |*/
/* |  Purpose -                                                     |*/
/* |  Project -                                                     |*/
/* |                                                                |*/
/* ------------------------------------------------------------------*/
/*                                                                   */
/*-------------------------------------------------------------------*/

-- 1. did either sku reach the time payment file, under ANY spelling
SELECT TPITEM, TPSRCD, TPPLAN, TPEXDATE
  FROM LSCPRDLIB.TPITEMSP
 WHERE TPITEM LIKE '1638%'
    OR TPITEM LIKE '1738%'
 ORDER BY TPITEM, TPSRCD;

-- 2. are they real items, and how is the sku actually spelled on the master
SELECT IISKU#, IIDESC
  FROM LSCPRDLIB.ITMMSTL1
 WHERE IISKU# LIKE '1638%'
    OR IISKU# LIKE '1738%'
 ORDER BY IISKU#;

-- 3. is the extension after the decimal point always two digits
SELECT LENGTH(RTRIM(SUBSTR(RTRIM(IISKU#),
                           POSSTR(RTRIM(IISKU#), '.') + 1))) AS EXT_LEN,
       COUNT(*) AS ITEMS
  FROM LSCPRDLIB.ITMMSTL1
 WHERE POSSTR(IISKU#, '.') > 0
 GROUP BY LENGTH(RTRIM(SUBSTR(RTRIM(IISKU#),
                              POSSTR(RTRIM(IISKU#), '.') + 1)))
 ORDER BY EXT_LEN;

-- 4. how many items can Excel mangle this way: all digits and a dot, ending
--    in zero, so the trailing zero is dropped when Excel makes it a number
SELECT COUNT(*) AS AT_RISK
  FROM LSCPRDLIB.ITMMSTL1
 WHERE POSSTR(IISKU#, '.') > 0
   AND RTRIM(IISKU#) LIKE '%0'
   AND RTRIM(TRANSLATE(RTRIM(IISKU#), ' ', '0123456789.')) = '';

-- 5. the same items listed out, to gauge which catalogs are affected
SELECT IISKU#, IIDESC
  FROM LSCPRDLIB.ITMMSTL1
 WHERE POSSTR(IISKU#, '.') > 0
   AND RTRIM(IISKU#) LIKE '%0'
   AND RTRIM(TRANSLATE(RTRIM(IISKU#), ' ', '0123456789.')) = ''
 ORDER BY IISKU#
 FETCH FIRST 100 ROWS ONLY;

-- 6. after the two rows are loaded, prove they are on file
SELECT TPITEM, TPSRCD, TPPLAN, TPEXDATE
  FROM LSCPRDLIB.TPITEMSP
 WHERE TPITEM IN ('1638.60', '1738.40')
 ORDER BY TPITEM, TPSRCD;
