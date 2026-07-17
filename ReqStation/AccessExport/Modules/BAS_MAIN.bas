' NOTE: connection-string password redacted during export
Option Explicit

Public Const web = "C:\Program Files\Mozilla Firefox\firefox.exe"
Const sqlConnect = "DSN=ABC;"
Public Const logo = "Littleton Coin Company"
'iSeries Access ODBC Driver
Const sqlDB2Connect = "ODBC;DRIVER={Client Access ODBC Driver(32bit)};DSN=AS400;SERVER=LCC1;UID=PICKAUTO;PWD=[REDACTED-see-IT];"

Public sqlDb As ADODB.Connection
Public sqlDb2 As ADODB.Connection
Public sqlCmd As ADODB.Command
Public sqlCmd2 As ADODB.Command
Public sqlRs As ADODB.Recordset
Public sqlRs2 As ADODB.Recordset

Public Station As Integer
Public appVer As String
Public openTime As String

Function get_path() As String
On Error GoTo Err_get_path

    get_path = sqlConnect

Exit_get_path:
    Exit Function
Err_get_path:
    MsgBox Error$
    Resume Exit_get_path
End Function

Function nixDate(myDate As Date) As String
    Dim yr, mth, day As String
    
    yr = DatePart("yyyy", myDate)
    If DatePart("m", myDate) < 10 Then mth = "0" & DatePart("m", myDate) Else mth = DatePart("m", myDate)
    If DatePart("d", myDate) < 10 Then day = "0" & DatePart("d", myDate) Else day = DatePart("d", myDate)
        
    nixDate = yr & "-" & mth & "-" & day
End Function

Function db2Date(myDate As Date) As String
    Dim yr, mth, day As String
    
    yr = DatePart("yyyy", myDate)
    If DatePart("m", myDate) < 10 Then mth = "0" & DatePart("m", myDate) Else mth = DatePart("m", myDate)
    If DatePart("d", myDate) < 10 Then day = "0" & DatePart("d", myDate) Else day = DatePart("d", myDate)
        
    db2Date = yr & mth & day
End Function

Function db2DateStr(db2Dt As String) As String
    Dim yr, mth, day As String
    
    yr = Left(db2Dt, 4)
    mth = Mid(db2Dt, 5, 2)
    day = Right(db2Dt, 2)
    
    db2DateStr = yr & "-" & mth & "-" & day
End Function

Function nixTime(myTime As Date) As String
    Dim hrs As String, min As String, sec As String
    
    hrs = DatePart("h", myTime)
    min = DatePart("n", myTime)
    sec = DatePart("s", myTime)
    
    If hrs < 10 Then hrs = "0" & hrs
    If min < 10 Then min = "0" & min
    If sec < 10 Then sec = "0" & sec
    
    nixTime = hrs & ":" & min & ":" & sec
End Function

Function getConnection(idb As Integer) As Integer
On Error GoTo Err_getConnection
    
    Select Case idb
    Case 1
        Set sqlDb = New ADODB.Connection
        sqlDb.Open get_path
    Case 2
        Set sqlDb2 = New ADODB.Connection
        sqlDb2.Open sqlDB2Connect
    End Select
    getConnection = 1
    
Exit_getConnection:
    Exit Function
Err_getConnection:
    getConnection = 0
    Resume Exit_getConnection
End Function

Function disConnect(idb As Integer)
On Error GoTo Err_disConnect
    
    Select Case idb
    Case 1
        sqlDb.Close
        Set sqlDb = Nothing
    Case 2
        sqlDb2.Close
        Set sqlDb2 = Nothing
    End Select
    
Exit_disConnect:
    Exit Function
Err_disConnect:
    MsgBox Error$
    Resume Exit_disConnect
End Function

Sub getStation()
On Error GoTo Err_getStation

    If getConnection(1) = 1 Then
        Dim sqlRs As ADODB.Recordset
        Dim sqlCmdString As String
        sqlCmdString = "Select `StationID#` From station WHERE Station='" & CurrentUser() & "'"
        Set sqlCmd = New ADODB.Command
        sqlCmd.ActiveConnection = sqlDb
        
        sqlCmd.CommandText = sqlCmdString
        Set sqlRs = sqlCmd.Execute
        
        If Not sqlRs.EOF Then Station = sqlRs![StationID#] Else Station = 99
        appVer = getAppVer("Requisitions")
        putActivity "Requisitions"
        Set sqlRs = Nothing
        Set sqlCmd = Nothing
        disConnect 1
    Else
        errCon 1
    End If
        
Exit_getStation:
    Exit Sub
Err_getStation:
    MsgBox Error$
    Resume Exit_getStation
End Sub

Sub putActivity(appName As String)
On Error GoTo Err_putActivity
    
    'open new activity record for logging and hold openTime to find record on close
    Dim sqlActRs As ADODB.Recordset
    Dim sqlActString As String
    
    sqlActString = "Insert Into activity (`StationID#`,appName,OpenTime,IP,user) " & _
                   "values('" & Station & "','" & appName & "','" & nixDate(Date) & " " & nixTime(Time()) & "','" & lastIP() & "','" & CurrentUser() & "')"
    
    openTime = "'" & nixDate(Date) & " " & nixTime(Time()) & "'"
    'MsgBox sqlActString
    sqlCmd.CommandText = sqlActString
    Set sqlActRs = sqlCmd.Execute
    
    Set sqlActRs = Nothing
        
Exit_putActivity:
    Exit Sub
Err_putActivity:
    MsgBox Error$
    Resume Exit_putActivity
End Sub

Sub updateActivity(appName As String)
On Error GoTo Err_updateActivity
    
    'update activity record for logging with close out time
    If getConnection(1) = 1 Then
        Dim sqlActRs As ADODB.Recordset
        Dim sqlActString As String
        
        sqlActString = "Update activity set CloseTime='" & nixDate(Date) & " " & nixTime(Time()) & "' " & _
                       "Where `StationID#`='" & Station & "' And appName='" & appName & "' And OpenTime=" & openTime
                       
        'MsgBox sqlActString
        Set sqlCmd = New ADODB.Command
        sqlCmd.ActiveConnection = sqlDb
        sqlCmd.CommandText = sqlActString
        Debug.Print sqlActString
        Set sqlActRs = sqlCmd.Execute
        
        Set sqlActRs = Nothing
        Set sqlCmd = Nothing
        disConnect 1
    Else
        MsgBox "Unable to update Activity.", vbCritical, logo
    End If
        
Exit_updateActivity:
    Exit Sub
Err_updateActivity:
    MsgBox Error$
    Resume Exit_updateActivity
End Sub

Function getAppVer(appName As String) As String
On Error GoTo Err_getAppVer

    Dim sqlAppRs As ADODB.Recordset
    Dim sqlAppString As String
    
    sqlAppString = "Select version From applications WHERE appName='" & appName & "'"
    
    sqlCmd.CommandText = sqlAppString
    Set sqlAppRs = sqlCmd.Execute
    
    If Not sqlAppRs.EOF Then getAppVer = sqlAppRs![Version] Else getAppVer = "N/A"
    Set sqlAppRs = Nothing
        
Exit_getAppVer:
    Exit Function
Err_getAppVer:
    MsgBox Error$
    Resume Exit_getAppVer
End Function

Function errCon(dsc As Integer) As String
On Error Resume Next
    Select Case dsc
    Case 1 'MySQL Connection error
        errCon = MsgBox("Error with MySQL Connection", vbCritical, "Littleton Coin Company")
    Case 2 'AS400 ODBC
        errCon = MsgBox("Error with AS400 ODBC Connection", vbCritical, "Littleton Coin Company")
    Case Else
        errCon = MsgBox("Danger Will Robinson!", vbCritical, "Littleton Coin Company")
    End Select
End Function
