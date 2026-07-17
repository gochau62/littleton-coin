Option Explicit

Private Sub chkReturnItem_AfterUpdate()
    
    If Me.chkReturnItem = 0 Then
        Me.date_returned = ""
    Else
        Me.date_returned = Date
    End If

    
End Sub

Private Sub cmdRefresh_Click()
    Me.Requery
End Sub

Private Sub Command30_Click()
On Error GoTo Err_Command30_Click

    Dim stDocName As String
    Dim linkCriteria As String
    

    stDocName = "Requested Material Summary"
    DoCmd.OpenReport stDocName, acPreview

Exit_Command30_Click:
    Exit Sub
Err_Command30_Click:
    MsgBox Err.Description
    Resume Exit_Command30_Click
End Sub

Private Sub Command9_Click()
On Error GoTo Err_Command9_Click

    Dim stDocName As String
    Dim linkCriteria As String
    

    stDocName = "rptRequest"
    linkCriteria = "req_num = " & Forms![frmMain]![req_num]
    DoCmd.OpenReport stDocName, acPreview, , linkCriteria

Exit_Command9_Click:
    Exit Sub
Err_Command9_Click:
    MsgBox Err.Description
    Resume Exit_Command9_Click
End Sub

Private Sub Command10_Click()
On Error GoTo Err_Command10_Click

    DoCmd.Quit

Exit_Command10_Click:
    Exit Sub
Err_Command10_Click:
    MsgBox Err.Description
    Resume Exit_Command10_Click
End Sub

Private Sub Form_Close()
On Error GoTo Err_Form_Close

    updateActivity "Requisitions"
    DoCmd.Quit
    
Exit_Form_Close:
    Exit Sub
Err_Form_Close:
    MsgBox Error$
    Resume Exit_Form_Close
End Sub

Private Sub Form_Load()
On Error GoTo Err_Form_Load

    getStation
    Me.Label28.Caption = "Req Station #" & Station
    Me.lblappVer.Caption = "Version:" & appVer
    Me.MainStation = Station
    
    Dim sodimm As String
    
    'Ad Insertion System version control
    If appVer <> Me.local_version.Caption Then
        Dim x
        MsgBox "Application updating..." & Chr(13) & "Please log back in.", vbCritical, "Littleton Coin Company"
        x = Shell("v:\copies\fix.bat Requisitions Requisitions", vbMaximizedFocus)
        Quit
    End If

Exit_Form_Load:
    Exit Sub
Err_Form_Load:
    MsgBox Err.Description
    Resume Exit_Form_Load
End Sub

Private Sub Form_Timer()
    Me.Refresh
End Sub

Private Sub Command20_Click()
On Error GoTo Err_Command20_Click

    Dim x As Variant
    x = Shell(web & " http://lcc1.littletoncoin.com:10088/requisitions/request.php", vbNormalFocus)
    
Exit_Command20_Click:
    Exit Sub
Err_Command20_Click:
    MsgBox Err.Description
    Resume Exit_Command20_Click
End Sub

Private Sub req_num_DblClick(Cancel As Integer)
On Error GoTo Err_req_num_DblClick

    Dim x As Variant
    If Me.req_num > 0 Then
        x = Shell(web & " http://lcc1.littletoncoin.com:10088/requisitions/request.php?id=" & Me.req_num, vbNormalFocus)
    Else
        x = Shell(web & " http://lcc1.littletoncoin.com:10088/requisitions/request.php", vbNormalFocus)
    End If
    
Exit_req_num_DblClick:
    Exit Sub
Err_req_num_DblClick:
    MsgBox Err.Description
    Resume Exit_req_num_DblClick
End Sub

Private Sub Command29_Click()
On Error GoTo Err_Command29_Click

    Screen.PreviousControl.SetFocus
    DoCmd.FindNext

Exit_Command29_Click:
    Exit Sub
Err_Command29_Click:
    MsgBox Err.Description
    Resume Exit_Command29_Click
End Sub
