/*-------------------------------------------------------------------*/
/*                                                                   */
/* Program Name      - TPDEVTBL                                      */
/*                                                                   */
/* Program Narrative - Build and drop the LSCDEVLIBP test tables for */
/*                     the time payment upload.  Same work as the    */
/*                     TPITEMSPDV and TPPRICEDV source members, but  */
/*                     written schema.table with a period so it runs */
/*                     as typed in ACS Run SQL Scripts, which uses   */
/*                     SQL naming.  The .TABLE and .PROC members     */
/*                     keep the library/file slash form because they */
/*                     are compiled with RUNSQLSTM under system      */
/*                     naming, the way every other loader is.        */
/*                     Run one statement at a time, not the whole    */
/*                     script - the drops are at the bottom.         */
/*                                                                   */
/* Author            - G Chau                                        */
/*                     Littleton Coin Company                        */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 09/14/2026                                    */
/* Run Instructions  - ACS Run SQL Scripts                           */
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

-- 1. dev copy of the time payment file, built by SQL so it has no size cap
DROP TABLE IF EXISTS LSCDEVLIBP.TPITEMSP;

CREATE TABLE LSCDEVLIBP.TPITEMSP LIKE LSCPRDLIB.TPITEMSP;

-- 2. optional seed with production data; leave it out to start from empty
INSERT INTO LSCDEVLIBP.TPITEMSP
  SELECT * FROM LSCPRDLIB.TPITEMSP;

-- 3. price table the TIMPAY002X stub answers from, only needed when testing
--    the blank plan path without calling the real OE0337R price module
DROP TABLE IF EXISTS LSCDEVLIBP.TPPRICEDV;

CREATE TABLE LSCDEVLIBP.TPPRICEDV (
 PRITEM CHAR(10) NOT NULL,          -- Item Number
 PRPRIC DECIMAL(9, 2) NOT NULL,     -- Retail Price the stub answers
 PRIMARY KEY (PRITEM));

LABEL ON TABLE LSCDEVLIBP.TPPRICEDV IS 'Dev price table for TIMPAY002S stub';

INSERT INTO LSCDEVLIBP.TPPRICEDV (PRITEM, PRPRIC) VALUES
 ('AB1075',      149.99),
 ('AB1076',      299.00),
 ('AB1077',      899.00),
 ('AB1079N.66', 2500.00),
 ('AB1086',       49.99),           -- under $99.00 on purpose, proves the skip
 ('1638.60',     249.00),           -- decimal skus, for the trailing zero test
 ('1738.40',     399.00);

-- 4. what is on the dev copy right now
SELECT TPITEM, TPSRCD, TPPLAN, TPEXDATE
  FROM LSCDEVLIBP.TPITEMSP
 ORDER BY TPITEM, TPSRCD;

-- 5. tear both dev tables down when testing is finished
DROP TABLE IF EXISTS LSCDEVLIBP.TPITEMSP;

DROP TABLE IF EXISTS LSCDEVLIBP.TPPRICEDV;
