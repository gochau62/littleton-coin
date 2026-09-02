/*-------------------------------------------------------------------*/
/*                                                                   */
/* Member Name       - PRJTRK_TABLES                                 */
/*                                                                   */
/* Narrative         - Two new files for the project screen:         */
/*                     the programmers on a project, each with a     */
/*                     work status of their own, and the comments    */
/*                     filed under each programmer's name.           */
/*                                                                   */
/* Author            - G Chau                                        */
/*                     Littleton Coin Company                        */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 09/01/2026                                    */
/* Run Instructions  - RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC)         */
/*                     SRCMBR(PRJTRK_TABLES) COMMIT(*NONE)           */
/*                     Change LSCDEVLIBP to LSCPRDLIB on promotion.  */
/*********************************************************************/
/* PR# on PRPROJP is the project key; PGDEVPRF on PRIDTRANSP is the  */
/* programmer; PRSCODE on PRSTATUSP is the status code.              */
/* __________________________________________________________________*/
/* |                  Maintenance History                           |*/
/* |                                                                |*/
/* | Programmer -                Date -                             |*/
/* |  Purpose -                                                     |*/
/* |  Project - 260082                                              |*/
/* |                                                                |*/
/* ------------------------------------------------------------------*/

-- one row per programmer on a project; the project's own PRPGMR stays
-- on PRPROJP and is shown here as the primary
CREATE TABLE LSCDEVLIBP/PRPGMASGP (
    PGAPROJ    DECIMAL(6, 0)  NOT NULL,
    PGAPGMR    CHAR(10)       NOT NULL,
    PGAWRKSTS  CHAR(3)        NOT NULL DEFAULT ' ',
    PGASTRDTE  DECIMAL(8, 0)  NOT NULL DEFAULT 0,
    PGAADDUSR  CHAR(10)       NOT NULL DEFAULT ' ',
    PGAADDDTE  DECIMAL(8, 0)  NOT NULL DEFAULT 0,
    PGAADDTME  DECIMAL(6, 0)  NOT NULL DEFAULT 0,
    PGACHGUSR  CHAR(10)       NOT NULL DEFAULT ' ',
    PGACHGDTE  DECIMAL(8, 0)  NOT NULL DEFAULT 0,
    PGACHGTME  DECIMAL(6, 0)  NOT NULL DEFAULT 0,
    PRIMARY KEY (PGAPROJ, PGAPGMR)
) RCDFMT PRPGMASGR;

LABEL ON TABLE LSCDEVLIBP/PRPGMASGP IS 'Project programmers';

LABEL ON COLUMN LSCDEVLIBP/PRPGMASGP (
    PGAPROJ   TEXT IS 'Project number',
    PGAPGMR   TEXT IS 'Programmer profile',
    PGAWRKSTS TEXT IS 'Work status code',
    PGASTRDTE TEXT IS 'Scheduled start date',
    PGAADDUSR TEXT IS 'Added by',
    PGAADDDTE TEXT IS 'Added date',
    PGAADDTME TEXT IS 'Added time',
    PGACHGUSR TEXT IS 'Changed by',
    PGACHGDTE TEXT IS 'Changed date',
    PGACHGTME TEXT IS 'Changed time'
);

-- comments filed under a programmer's name; rows are flagged, never
-- deleted, so the weekly report keeps its history
CREATE TABLE LSCDEVLIBP/PRPGMCMTP (
    PGCSEQ     INTEGER        GENERATED ALWAYS AS IDENTITY
                              (START WITH 1 INCREMENT BY 1) NOT NULL,
    PGCPROJ    DECIMAL(6, 0)  NOT NULL,
    PGCPGMR    CHAR(10)       NOT NULL,
    PGCUSER    CHAR(10)       NOT NULL,
    PGCDATE    DECIMAL(8, 0)  NOT NULL,
    PGCTIME    DECIMAL(6, 0)  NOT NULL,
    PGCTEXT    VARCHAR(4000)  NOT NULL DEFAULT '',
    PGCDELETE  CHAR(1)        NOT NULL DEFAULT ' ',
    PRIMARY KEY (PGCSEQ)
) RCDFMT PRPGMCMTR;

CREATE INDEX LSCDEVLIBP/PRPGMCMTL1
    ON LSCDEVLIBP/PRPGMCMTP (PGCPROJ, PGCPGMR, PGCDATE, PGCTIME);

LABEL ON TABLE LSCDEVLIBP/PRPGMCMTP IS 'Project programmer comments';

LABEL ON COLUMN LSCDEVLIBP/PRPGMCMTP (
    PGCSEQ    TEXT IS 'Comment sequence',
    PGCPROJ   TEXT IS 'Project number',
    PGCPGMR   TEXT IS 'Filed under programmer',
    PGCUSER   TEXT IS 'Written by',
    PGCDATE   TEXT IS 'Comment date',
    PGCTIME   TEXT IS 'Comment time',
    PGCTEXT   TEXT IS 'Comment text',
    PGCDELETE TEXT IS 'Y when removed'
);
