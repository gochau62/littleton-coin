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

-- 6a. everything the Holiday Gift upload put on file under the GIO source
--     code, sorted so the two decimal skus show up together if present
SELECT TPITEM, TPSRCD, TPPLAN, TPEXDATE
  FROM LSCPRDLIB.TPITEMSP
 WHERE TPSRCD = 'GIO'
 ORDER BY TPITEM;

-- 7. the one case the upload cannot tell apart: an all-digit sku with a
--    one digit extension AND its two digit twin both on the master, so a
--    number cell holding the twin lands on the one digit item instead.
--    Zero rows here means the fix can never pick a wrong item.
SELECT COUNT(*) AS TWINS
  FROM LSCPRDLIB.ITMMSTL1 A
  JOIN LSCPRDLIB.ITMMSTL1 B
    ON B.IISKU# = RTRIM(A.IISKU#) || '0'
 WHERE POSSTR(A.IISKU#, '.') > 0
   AND LENGTH(RTRIM(SUBSTR(RTRIM(A.IISKU#),
                           POSSTR(RTRIM(A.IISKU#), '.') + 1))) = 1
   AND RTRIM(TRANSLATE(RTRIM(A.IISKU#), ' ', '0123456789.')) = '';

-- 8. the same pairs listed, with both descriptions, to judge how alike
--    the two items are and whether Marketing would ever key the short one
SELECT RTRIM(A.IISKU#) AS ONE_DIGIT, RTRIM(A.IIDESC) AS ONE_DESC,
       RTRIM(B.IISKU#) AS TWO_DIGIT, RTRIM(B.IIDESC) AS TWO_DESC
  FROM LSCPRDLIB.ITMMSTL1 A
  JOIN LSCPRDLIB.ITMMSTL1 B
    ON B.IISKU# = RTRIM(A.IISKU#) || '0'
 WHERE POSSTR(A.IISKU#, '.') > 0
   AND LENGTH(RTRIM(SUBSTR(RTRIM(A.IISKU#),
                           POSSTR(RTRIM(A.IISKU#), '.') + 1))) = 1
   AND RTRIM(TRANSLATE(RTRIM(A.IISKU#), ' ', '0123456789.')) = ''
 ORDER BY A.IISKU#
 FETCH FIRST 200 ROWS ONLY;
