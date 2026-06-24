<?php 
// this script is called from a bash script that is set up as a cron job on the IBM i:
// bash script location: /home/timmy01/purge_UPS_SFTP_Files.sh
// crontab location: /QOpenSys/etc/crontab
// project #: 230085 - kjr - 04/24/23

ini_set('memory_limit', '512M');
set_time_limit(900); // 15 minutes
include "../LCCOnline/Utils/default_values.php";
include 'EZMail.php';


$archivePath = "/www/seidenphp/htdocs/utils/UPS_Tracking_Archive/archive/";
$deletedCount = 0; // - WO#71883 gc

// delete anything older than a month in the outbound folder, otherwise, move to the archive folder and keep for two months
$dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/utils/UPS_Tracking_Archive/outbound/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
        $absoluteDirWithoutFile = $file->getPath();    
        $pathToFile = $file->getPathname();
        $filenameOnly = $file->getFilename(); 
        if (time()-filemtime($pathToFile) > (24 * 31) * 3600) { // older than 1 month (31 days)
           
            unlink($pathToFile); // delete file
            $deletedCount++;
            //echo time()-filemtime($pathToFile) . " " . $pathToFile . " <br>"; // used in testing when called from a browser
            
        }
        else {
            
            rename($pathToFile, $archivePath . $filenameOnly); // move file to archive folder
            
        }
        
        }
    }
}

// delete any file in the archive directory that's older than two months
$dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/utils/UPS_Tracking_Archive/archive/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 62) * 3600) { // older than 2 months (62 days)
                
                unlink($pathToFile);
                $deletedCount++;

            }

        }
    }
}

// delete any file in the LCCOnline_logs directory that's older than a month
$dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/LCCOnline/LCCOnline_logs/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 31) * 3600) { // older than 1 month (31 days)
                if (trim($filenameOnly) != "readme.txt") { // don't delete the informational file about the process of LCCOnline_logs
                    unlink($pathToFile);
                    $deletedCount++;
                } 

            }

        }
    }
}

// delete any file in /www/seidendev/htdocs/LCCOnline/LCCOnline_logs/ older than 1 month - WO#71883 gc
$dir = new DirectoryIterator('/www/seidendev/htdocs/LCCOnline/LCCOnline_logs');
foreach ($dir as $file) {
    if (!$file->isDot() && !$file->isDir()) {
        $pathToFile = $file->getPathname();
        $filenameOnly = $file->getFilename();
        if (time() - filemtime($pathToFile) > (24 * 31) * 3600) {
            if (trim(strtolower($filenameOnly)) != 'readme.txt') {
                if (@unlink($pathToFile)) { $deletedCount++; }
            }
        }
    }
}

// delete any file in /www/playground/htdocs/LCCOnline/LCCOnline_logs/ older than 1 month - WO#71883 gc
$dir = new DirectoryIterator('/www/playground/htdocs/LCCOnline/LCCOnline_logs');
foreach ($dir as $file) {
    if (!$file->isDot() && !$file->isDir()) {
        $pathToFile = $file->getPathname();
        $filenameOnly = $file->getFilename();
        if (time() - filemtime($pathToFile) > (24 * 31) * 3600) {
            if (trim(strtolower($filenameOnly)) != 'readme.txt') {
                if (@unlink($pathToFile)) { $deletedCount++; }
            }
        }
    }
}

// delete any file in the sftptxt directory that's older than two months - these are files used/given by CCS
$dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/utils/sftptxt/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 62) * 3600) { // older than 2 months (62 days)

                unlink($pathToFile);
                $deletedCount++;

            }

        }
    }
}

// delete any file in the MatrixDunningResend/Archive directory that's older than two months - these are files used by Credit for dunning resends (Project #230161)
$dir = new DirectoryIterator(dirname('/Shared/MatrixDunningResend/Archive/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 62) * 3600) { // older than 2 months (62 days)

                unlink($pathToFile);
                $deletedCount++;

            }

        }
    }
}

// delete any file in the MatrixBalDueResend/Archive directory that's older than two months - these are files used by Credit for bal due letter resends (Project #230207))
$dir = new DirectoryIterator(dirname('/Shared/MatrixBalDueResend/Archive/*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 62) * 3600) { // older than 2 months (62 days)

                unlink($pathToFile);
                $deletedCount++;

            }

        }
    }
}

// delete any php error log file in the /www/seidenphp/logs/ directory that's older than one month - WO#65921 - kjr
$dir = new DirectoryIterator(dirname('/www/seidenphp/logs/php.log*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 31) * 3600) { // older than 1 month (31 days)

                unlink($pathToFile);
                $deletedCount++;

            }

        }
    }
}

// delete any file in this directory on a daily basis - I'll be switching the cron job to run early in the morning (6:32AM) to avoid ICOM time on the weekdays, but also early enough to avoid users messing around with the app - Project# 240015 - kjr

// KJR - this project still hasn't been properly implemented, though the functionality does exist in production - it would appear that no one uses the attachment functionality though, as there's no files in the location below
// 08-13-25 - I'm commenting out below until I find that we need it

// $dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/LCCOnline/TrackIt_Attach/*'));
// foreach ($dir as $file) {
//     if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
//         if (!$file->isDir()) { // avoid any action on directories
//             $absoluteDirWithoutFile = $file->getPath();
//             $pathToFile = $file->getPathname();
//             $filenameOnly = $file->getFilename();
//              // Delete files daily - this is supposed to be only a temporary location so that the files can be attached to e-mails

//             unlink($pathToFile);
                 

            

//         }
//     }
// }

// the below from Gordon Chau's WO#71833

// delete any php error log file in the /www/seidendev/logs/ directory that's older than one month - WO#71883 gc
$dir = new DirectoryIterator(dirname('/www/seidendev/logs/php.log*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 31) * 3600) { // older than 1 month (31 days)

                unlink($pathToFile);
                $deletedCount++; 

            }

        }
    }
}

// delete any php error log file in the /www/playground/logs/ directory that's older than one month - WO#71883 gc
$dir = new DirectoryIterator(dirname('/www/playground/logs/php.log*'));
foreach ($dir as $file) {
    if (!$file->isDot()) { // ignore . (current dir) and .. (parent dir) file links
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            if (time()-filemtime($pathToFile) > (24 * 31) * 3600) { // older than 1 month (31 days)

                unlink($pathToFile);
                $deletedCount++; 

            }

        }
    }
}

$clarioLogFile = '/www/seidenphp/htdocs/utils/Clario_SFTP_Pull/ClarioSFTP_pull.log'; 
// delete the Clario customer segment files pulled by ClarioSFTP_pull.php that are older than six months (allotted ) - PR#260011
$dir = new DirectoryIterator(dirname('/www/seidenphp/htdocs/utils/Clario_SFTP_Pull/customer_segments_*'));
foreach ($dir as $file) {
    if (!$file->isDot()) {
        if (!$file->isDir()) { // avoid any action on directories
            $absoluteDirWithoutFile = $file->getPath();
            $pathToFile = $file->getPathname();
            $filenameOnly = $file->getFilename();
            // only act on the pulled Clario data files - leave the ClarioSFTP_pull.log
            if (strpos($filenameOnly, 'customer_segments_') === 0 && substr($filenameOnly, -4) === '.txt') {
                if (time()-filemtime($pathToFile) > (24 * 183) * 3600) { // older than 6 months (183 days)
                    unlink($pathToFile);
                    $deletedCount++;
                    if (unlink($pathToFile)) {
                        $deletedCount++;
                        // log the deletion to the Clario log, same timestamp format the pull script uses
                        @file_put_contents($clarioLogFile, date('Y-m-d H:i:s') . ' Purged ' . $filenameOnly . ' (older than six months) ' . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
    }
}


$emailRecipient = 'krainville@littletoncoin.com'; // just send e-mail to Kyle for now, there's a message sent to QSYSOPR message queue in bash script which calls this program, located in /home/timmy01/purge_UPS_SFTP_Files.sh
//$emailRecipient = 'krainville@littletoncoin.com';
$subject = "IFS Purge Cron Job Complete";
//$subject = "This is just a test: C3 rollover job was was shutdown prematurely";
$message = "The IFS File Purge Cron Job has completed successfully. The cron job entry for the initiation of this job can be found in /QOpenSys/etc/crontab on LCC1. <br><br> Originating program: /www/seidenphp/htdocs/utils/CS_PIC_TRK_PURGE.php"
    . "<br><br><strong>Total files purged: {$deletedCount}</strong>"; // - WO#71883 gc
$sender = "lcc1@littletoncoin.com";
$failAddress = "helpdesk@littletoncoin.com";
//$failAddress = "krainville@littletoncoin.com";
$attachedFile = false;
sendMSG($emailRecipient, $subject, $message, $sender, $failAddress, $attachedFile);
?>