/*-------------------------------------------------------------------*/
/*                                                                   */
/* Member Name       - PRJTRK_TABLES                                 */
/*                                                                   */
/* Narrative         - One file for the project screen: the          */
/*                     programmers on a project, each with a work    */
/*                     status and scheduled start of their own.      */
/*                     Comments are not here - the screen keeps      */
/*                     using WebNotes, and PRCHGLOGP records who     */
/*                     changed what and when.                        */
/*                                                                   */
/* Author            - G Chau                                        */
/*                     Littleton Coin Company                        */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 09/11/2026                                    */
/* Run Instructions  - RUNSQLSTM SRCFILE(QSQLSRC)                    */
/*                     SRCMBR(PRJTRK_TABLES) COMMIT(*NONE)           */
/*                     Unqualified, so the library list decides.     */
/*********************************************************************/
/* __________________________________________________________________*/
/* |                  Maintenance History                           |*/
/* |                                                                |*/
/* | Programmer -                Date -                             |*/
/* |  Purpose -                                                     |*/
/* |  Project - 260082                                              |*/
/* |                                                                |*/
/* ------------------------------------------------------------------*/

-- one row per extra programmer; the project's own PRPGMR stays on PRPROJP
CREATE TABLE PRPGMRASGT (
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
) RCDFMT PRPGMRASGR;

LABEL ON TABLE PRPGMRASGT IS 'Project programmers';

LABEL ON COLUMN PRPGMRASGT (
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
