-- Requisition Station: (re)load the BADGE dropdown list in RQSCODEFLT.
-- Sources: employee tbl DeptID# 30/36 (15 rows, partial - complete from
-- SELECT EmpID#, FName, LName FROM [employee tbl] WHERE DeptID# IN (30,36))
-- and the .mdb Inventory Data Entry Table (6 rows).
-- Re-runnable: clears the BADGE type first, then inserts everything.
-- Run in STRSQL, or RUNSQLSTM from a QSQLSRC member. Qualify the library
-- to match where RQSCODEFLT lives (LSCDEVLIBP in dev).

DELETE FROM LSCDEVLIBP/RQSCODEFLT WHERE CDTYPE = 'BADGE';

INSERT INTO LSCDEVLIBP/RQSCODEFLT (CDTYPE, CDCODE, CDDESC, CDACTV) VALUES
('BADGE','2090','Alexis Quinn','Y'),
('BADGE','3072','Diane McLain','Y'),
('BADGE','3076','Cecelia Holladay','Y'),
('BADGE','88888','Dawn Belliveau','Y'),
('BADGE','99999','Heidi Morrison','Y'),
('BADGE','111111','Suzi Murrro','Y'),
('BADGE','1150','Sara Walker','Y'),
('BADGE','505050','Melinda Wojdylak','Y'),
('BADGE','1228','Melissa Peters','Y'),
('BADGE','222222','Sara Walker','Y'),
('BADGE','1165','Melissa Peters','Y'),
('BADGE','581','Auto Bagger','Y'),
('BADGE','582','Auto Bagger (Night)','Y'),
('BADGE','525','Pak Rapid(Night)','Y'),
('BADGE','527','Pak Rapid','Y'),
('BADGE','KS1','Kirby Simino','Y'),
('BADGE','MP1','Melissa Peters','Y'),
('BADGE','MW1','Melinda Wojdylak','Y'),
('BADGE','PK1','Pam Kathan','Y'),
('BADGE','PT1','Patty Tholl','Y'),
('BADGE','SW1','Sara Walker','Y');

-- Verify: expect 21
-- SELECT COUNT(*) FROM LSCDEVLIBP/RQSCODEFLT WHERE CDTYPE = 'BADGE';
