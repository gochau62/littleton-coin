/*-------------------------------------------------------------------*/
/*                                                                   */
/* Member Name       - PRSTATUSP_INQUEUE                             */
/*                                                                   */
/* Narrative         - Adds In Queue to the Programmer work status   */
/*                     dropdown on the project screen. The dropdown  */
/*                     is loaded from PRSTATUSP through PTS0024S     */
/*                     (getRecsPRSTATUSP in PROJ_model.php), so a    */
/*                     row is all it takes.                          */
/*                                                                   */
/* Author            - G Chau                                        */
/*                     Littleton Coin Company                        */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 09/01/2026                                    */
/* Run Instructions  - RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC)         */
/*                     SRCMBR(PRSTATUSP_INQUEUE) COMMIT(*NONE)       */
/*********************************************************************/
/* PRSCODE and PRSDESC are the two columns the screen reads. If      */
/* DSPFFD shows more without defaults, add them here.                */
/* __________________________________________________________________*/
/* |                  Maintenance History                           |*/
/* |                                                                |*/
/* | Programmer -                Date -                             |*/
/* |  Purpose -                                                     |*/
/* |  Project - 260082                                              |*/
/* |                                                                |*/
/* ------------------------------------------------------------------*/

INSERT INTO LSCPRDLIB/PRSTATUSP (PRSCODE, PRSDESC)
SELECT 'INQ', 'In Queue'
  FROM SYSIBM/SYSDUMMY1
 WHERE NOT EXISTS (SELECT 1 FROM LSCPRDLIB/PRSTATUSP
                    WHERE RTRIM(PRSCODE) = 'INQ');
