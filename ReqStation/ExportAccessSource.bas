Attribute VB_Name = "ExportAccessSource"
Option Explicit

'***************************************************************************
'**                                                                       **
'**  Program Name :  ExportAccessSource                                   **
'**  Application  :  Req Station v1.0.3  -  Littleton Coin Company        **
'**                                                                       **
'**  Narrative    :  One-shot export of every Access object to plain      **
'**                  text so all source can be committed to git for the   **
'**                  Db2-for-i migration.  Writes forms, reports, macros  **
'**                  and modules via SaveAsText (form/report code-behind  **
'**                  is embedded in the form/report output), query SQL,   **
'**                  table schemas (linked-table connect strings have     **
'**                  passwords scrubbed), relationships, database         **
'**                  properties, VBA references, and CSV data for every   **
'**                  local table.  Data macros are attempted per table.   **
'**                                                                       **
'**  Run          :  1. Open the database and press Alt+F11 (VBA editor). **
'**                  2. File > Import File... and pick this .bas file.    **
'**                  3. Run ExportAllSource (F5, or type ExportAllSource  **
'**                     in the Immediate window and press Enter).         **
'**                  4. Output lands in an AccessExport folder created    **
'**                     next to the .accdb/.mdb.  Check _export_log.txt   **
'**                     for any per-object errors before committing.      **
'**                                                                       **
'***************************************************************************

Private m_sLogPath As String
Private m_lErrors As Long
Private m_colUsedFiles As Collection   ' output paths already claimed this run (collision guard)

'---------------------------------------------------------------------------
' Entry point - run this.
'---------------------------------------------------------------------------
Public Sub ExportAllSource()
    Dim sRoot As String
    Dim lForms As Long, lReports As Long, lMacros As Long, lModules As Long
    Dim lQueries As Long, lTables As Long, lRelations As Long, lData As Long
    Dim lProps As Long, lRefs As Long
    Dim lErrNum As Long, sErrDesc As String

    On Error GoTo Fatal

    sRoot = CurrentProject.Path & "\AccessExport\"
    m_sLogPath = sRoot & "_export_log.txt"
    m_lErrors = 0
    Set m_colUsedFiles = New Collection

    EnsureFolder sRoot
    ResetLog
    EnsureCleanFolder sRoot & "Modules\"
    EnsureCleanFolder sRoot & "Forms\"
    EnsureCleanFolder sRoot & "Reports\"
    EnsureCleanFolder sRoot & "Macros\"
    EnsureCleanFolder sRoot & "Queries\"
    EnsureCleanFolder sRoot & "Tables\"
    EnsureCleanFolder sRoot & "Data\"

    LogLine "===== Export started: " & CurrentProject.FullName & " ====="

    lForms = ExportForms(sRoot & "Forms\")
    lReports = ExportReports(sRoot & "Reports\")
    lMacros = ExportMacros(sRoot & "Macros\")
    lModules = ExportModules(sRoot & "Modules\")
    lQueries = ExportQueries(sRoot & "Queries\")
    lTables = ExportTableDefs(sRoot & "Tables\")
    lRelations = ExportRelationships(sRoot & "Tables\")
    lProps = ExportDbProperties(sRoot & "Tables\")
    lRefs = ExportReferences(sRoot & "Modules\")
    lData = ExportData(sRoot & "Data\")

    LogLine "===== Export finished.  Errors: " & m_lErrors & " ====="

    MsgBox "Req Station source export complete." & vbCrLf & vbCrLf & _
           "Forms:          " & lForms & vbCrLf & _
           "Reports:        " & lReports & vbCrLf & _
           "Macros:         " & lMacros & vbCrLf & _
           "Modules:        " & lModules & vbCrLf & _
           "Queries:        " & lQueries & vbCrLf & _
           "Table schemas:  " & lTables & vbCrLf & _
           "Relationships:  " & lRelations & vbCrLf & _
           "Db properties:  " & lProps & vbCrLf & _
           "References:     " & lRefs & vbCrLf & _
           "Data files:     " & lData & vbCrLf & vbCrLf & _
           "Errors:         " & m_lErrors & vbCrLf & _
           "Log:            " & m_sLogPath, _
           IIf(m_lErrors > 0, vbExclamation, vbInformation), "ExportAccessSource"
    Exit Sub

Fatal:
    lErrNum = Err.Number
    sErrDesc = Err.Description
    On Error Resume Next
    LogLine "FATAL Err " & lErrNum & ": " & sErrDesc
    MsgBox "Export aborted by a fatal error." & vbCrLf & vbCrLf & _
           "Err " & lErrNum & ": " & sErrDesc & vbCrLf & vbCrLf & _
           "Log (if created): " & m_sLogPath, vbCritical, "ExportAccessSource"
End Sub

'---------------------------------------------------------------------------
' SaveAsText exports (forms / reports / macros / modules)
'---------------------------------------------------------------------------
Private Function ExportForms(sFolder As String) As Long
    Dim ao As AccessObject
    On Error GoTo Fail        ' one bad object must not abort the whole run
    For Each ao In CurrentProject.AllForms
        CloseIfLoaded acForm, ao
        If SaveObjectAsText(acForm, "Form", ao.Name, sFolder, ".frm") Then
            ExportForms = ExportForms + 1
        End If
    Next ao
    Exit Function
Fail:
    LogFailure "Forms enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportReports(sFolder As String) As Long
    Dim ao As AccessObject
    On Error GoTo Fail
    For Each ao In CurrentProject.AllReports
        CloseIfLoaded acReport, ao
        If SaveObjectAsText(acReport, "Report", ao.Name, sFolder, ".rpt") Then
            ExportReports = ExportReports + 1
        End If
    Next ao
    Exit Function
Fail:
    LogFailure "Reports enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportMacros(sFolder As String) As Long
    Dim ao As AccessObject
    On Error GoTo Fail
    For Each ao In CurrentProject.AllMacros
        If SaveObjectAsText(acMacro, "Macro", ao.Name, sFolder, ".mcr") Then
            ExportMacros = ExportMacros + 1
        End If
    Next ao
    Exit Function
Fail:
    LogFailure "Macros enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportModules(sFolder As String) As Long
    ' Covers standard AND class modules; form/report code-behind is
    ' already embedded in the SaveAsText output of the form/report.
    Dim ao As AccessObject
    On Error GoTo Fail
    For Each ao In CurrentProject.AllModules
        If SaveObjectAsText(acModule, "Module", ao.Name, sFolder, ".bas") Then
            ExportModules = ExportModules + 1
        End If
    Next ao
    Exit Function
Fail:
    LogFailure "Modules enumeration", "(object skipped)"
    Resume Next
End Function

Private Sub CloseIfLoaded(lType As AcObjectType, ao As AccessObject)
    ' SaveAsText needs the object closed; never save design changes here.
    On Error Resume Next
    If ao.IsLoaded Then
        DoCmd.Close lType, ao.Name, acSaveNo
        LogLine "NOTE  Closed open object [" & ao.Name & "] before export"
    End If
End Sub

Private Function SaveObjectAsText(lType As AcObjectType, sLabel As String, _
        sRealName As String, sFolder As String, sExt As String) As Boolean
    Dim sFile As String
    On Error GoTo Fail
    sFile = UniqueFile(sFolder, SafeName(sRealName), sExt, sLabel & " [" & sRealName & "]")
    Application.SaveAsText lType, sRealName, sFile
    LogLine "OK    " & sLabel & " [" & sRealName & "] -> " & sFile
    SaveObjectAsText = True
    Exit Function
Fail:
    LogFailure sLabel, sRealName
    On Error Resume Next
    Kill sFile                ' don't leave a partial file looking exported
End Function

'---------------------------------------------------------------------------
' VBA project references - needed to recompile the exported code
'---------------------------------------------------------------------------
Private Function ExportReferences(sFolder As String) As Long
    Dim ref As Reference, f As Integer, sFile As String
    On Error GoTo Fail
    f = FreeFile
    sFile = UniqueFile(sFolder, "_references", ".txt", "VBA references")
    Open sFile For Output As #f
    Print #f, "VBA project references: " & CurrentProject.Name
    For Each ref In Application.References
        On Error Resume Next    ' FullPath/Guid can raise on broken references
        Print #f, ""
        Print #f, "Name: " & ref.Name
        Print #f, "  FullPath: " & ref.FullPath
        Print #f, "  Guid: " & ref.Guid & "  Version: " & ref.Major & "." & ref.Minor
        Print #f, "  IsBroken: " & ref.IsBroken
        On Error GoTo Fail
        ExportReferences = ExportReferences + 1
    Next ref
    Close #f
    LogLine "OK    References (" & ExportReferences & ") -> " & sFile
    Exit Function
Fail:
    LogFailure "References", "_references.txt"
    On Error Resume Next
    Close #f
    Kill sFile
End Function

'---------------------------------------------------------------------------
' Queries: one .sql file each, parameters noted as comment lines
'---------------------------------------------------------------------------
Private Function ExportQueries(sFolder As String) As Long
    Dim db As DAO.Database, qdf As DAO.QueryDef
    On Error GoTo Fail
    Set db = CurrentDb
    For Each qdf In db.QueryDefs
        If Left$(qdf.Name, 1) <> "~" Then    ' skip hidden form recordsources
            If ExportOneQuery(qdf, sFolder) Then
                ExportQueries = ExportQueries + 1
            End If
        End If
    Next qdf
    Exit Function
Fail:
    LogFailure "Queries enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportOneQuery(qdf As DAO.QueryDef, sFolder As String) As Boolean
    Dim f As Integer, prm As DAO.Parameter, sFile As String
    On Error GoTo Fail
    f = FreeFile
    sFile = UniqueFile(sFolder, SafeName(qdf.Name), ".sql", "Query [" & qdf.Name & "]")
    Open sFile For Output As #f
    Print #f, "-- Query: " & qdf.Name
    Print #f, "-- Type: " & qdf.Type
    If Len(qdf.Connect) > 0 Then    ' pass-through: SQL below is backend dialect
        Print #f, "-- Connect: " & ScrubPassword(qdf.Connect)
        Print #f, "-- ReturnsRecords: " & qdf.ReturnsRecords
    End If
    For Each prm In qdf.Parameters
        Print #f, "-- Parameter: " & prm.Name & "  (" & DaoTypeName(prm.Type) & ")"
    Next prm
    Print #f, qdf.SQL
    Close #f
    LogLine "OK    Query [" & qdf.Name & "]"
    ExportOneQuery = True
    Exit Function
Fail:
    LogFailure "Query", qdf.Name
    On Error Resume Next
    Close #f
    Kill sFile                ' don't leave a partial file looking exported
End Function

'---------------------------------------------------------------------------
' Table schemas: fields, types, indexes; linked tables get a scrubbed
' connect string instead of a data export
'---------------------------------------------------------------------------
Private Function ExportTableDefs(sFolder As String) As Long
    Dim db As DAO.Database, td As DAO.TableDef
    On Error GoTo Fail
    Set db = CurrentDb
    For Each td In db.TableDefs
        If Not IsSkippableTable(td.Name) Then
            If ExportOneTableDef(td, sFolder) Then
                ExportTableDefs = ExportTableDefs + 1
            End If
            ExportDataMacros td, sFolder
        End If
    Next td
    Exit Function
Fail:
    LogFailure "Tables enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportOneTableDef(td As DAO.TableDef, sFolder As String) As Boolean
    Dim f As Integer, fld As DAO.Field, idx As DAO.Index, ixf As DAO.Field
    Dim sLine As String, sFile As String
    On Error GoTo Fail
    f = FreeFile
    sFile = UniqueFile(sFolder, SafeName(td.Name), ".txt", "Table schema [" & td.Name & "]")
    Open sFile For Output As #f
    Print #f, "Table: " & td.Name
    If Len(td.Connect) > 0 Then
        Print #f, "Linked: YES"
        Print #f, "Connect: " & ScrubPassword(td.Connect)
        Print #f, "SourceTable: " & td.SourceTableName
    Else
        Print #f, "Linked: NO (local)"
    End If
    Print #f, ""
    Print #f, "Fields:"
    For Each fld In td.Fields
        sLine = "  " & fld.Name & "  " & DaoTypeName(fld.Type) & _
                "  Size=" & fld.Size & "  Required=" & fld.Required
        If fld.Type = dbText Or fld.Type = dbMemo Then
            sLine = sLine & "  AllowZeroLength=" & fld.AllowZeroLength
        End If
        If (fld.Attributes And dbAutoIncrField) <> 0 Then
            sLine = sLine & "  AUTONUMBER"
        End If
        If Len(fld.DefaultValue & "") > 0 Then
            sLine = sLine & "  Default=" & fld.DefaultValue
        End If
        Print #f, sLine
        ' Full property dump catches ValidationRule/ValidationText, Format,
        ' InputMask, Caption, Description, DisplayControl/RowSource, etc.
        PrintDaoProperties f, "      ", fld.Properties
    Next fld
    Print #f, ""
    Print #f, "Table properties:"
    PrintDaoProperties f, "  ", td.Properties
    Print #f, ""
    Print #f, "Indexes:"
    If Len(td.Connect) = 0 Then
        For Each idx In td.Indexes
            Print #f, "  " & idx.Name & IIf(idx.Primary, "  PRIMARY", "") & _
                      IIf(idx.Unique, "  UNIQUE", "")
            For Each ixf In idx.Fields
                Print #f, "    " & ixf.Name
            Next ixf
        Next idx
    Else
        ' DAO raises 3251 reading Indexes on an attached table.
        Print #f, "  (not available for linked tables via DAO - query the backend, e.g. SHOW INDEX)"
    End If
    Close #f
    LogLine "OK    Table schema [" & td.Name & "]"
    ExportOneTableDef = True
    Exit Function
Fail:
    LogFailure "Table schema", td.Name
    On Error Resume Next
    Close #f
    Kill sFile                ' don't leave a partial file looking exported
End Function

' ACCDB table-level data macros (After Insert/After Update etc.).
' SaveAsText type 12 raises on .mdb files and on tables with no data
' macros; that is logged as a NOTE so the absence is documented.
Private Sub ExportDataMacros(td As DAO.TableDef, sFolder As String)
    Dim sFile As String
    On Error GoTo Fail
    sFile = UniqueFile(sFolder, SafeName(td.Name) & "_datamacros", ".xml", "Data macros [" & td.Name & "]")
    Application.SaveAsText 12, td.Name, sFile    ' 12 = acTableDataMacro (constant absent pre-2010)
    LogLine "OK    Data macros [" & td.Name & "] -> " & sFile
    Exit Sub
Fail:
    LogLine "NOTE  Data macros [" & td.Name & "] - none, or not applicable (Err " & Err.Number & ")"
    On Error Resume Next
    Kill sFile
End Sub

' Print every property in a DAO Properties collection.  Many DAO
' properties raise errors when read while unset, so each value is read
' under its own On Error Resume Next.
Private Sub PrintDaoProperties(f As Integer, sIndent As String, prps As DAO.Properties)
    Dim prp As DAO.Property, sVal As String
    On Error Resume Next
    For Each prp In prps
        Err.Clear
        sVal = ""
        sVal = CStr(prp.Value)
        If Err.Number = 0 Then
            Print #f, sIndent & prp.Name & "=" & sVal
        Else
            Err.Clear
            Print #f, sIndent & prp.Name & "  (value not readable)"
        End If
    Next prp
End Sub

Private Function ExportRelationships(sFolder As String) As Long
    Dim db As DAO.Database, rel As DAO.Relation, fld As DAO.Field
    Dim f As Integer, lCount As Long, sFile As String
    On Error GoTo Fail
    Set db = CurrentDb
    f = FreeFile
    sFile = UniqueFile(sFolder, "_relationships", ".txt", "Relationships")
    Open sFile For Output As #f
    Print #f, "Relationships: " & CurrentProject.Name
    For Each rel In db.Relations
        Print #f, ""
        Print #f, "Name: " & rel.Name
        Print #f, "  Table: " & rel.Table & "  ->  ForeignTable: " & rel.ForeignTable
        For Each fld In rel.Fields
            Print #f, "  Field: " & fld.Name & " -> " & fld.ForeignName
        Next fld
        Print #f, "  Enforced=" & CStr((rel.Attributes And dbRelationDontEnforce) = 0) & _
                  "  CascadeUpdate=" & CStr((rel.Attributes And dbRelationUpdateCascade) <> 0) & _
                  "  CascadeDelete=" & CStr((rel.Attributes And dbRelationDeleteCascade) <> 0)
        lCount = lCount + 1
    Next rel
    Close #f
    LogLine "OK    Relationships (" & lCount & ") -> " & sFile
    ExportRelationships = lCount
    Exit Function
Fail:
    LogFailure "Relationships", "_relationships.txt"
    On Error Resume Next
    Close #f
    Kill sFile                ' don't leave a partial file looking exported
End Function

'---------------------------------------------------------------------------
' Database/startup properties (StartUpForm, AllowBypassKey, AppTitle...)
' - load-bearing behavior for a locked-down switchboard app
'---------------------------------------------------------------------------
Private Function ExportDbProperties(sFolder As String) As Long
    Dim db As DAO.Database, f As Integer, sFile As String
    Dim aop As AccessObjectProperty, sVal As String
    On Error GoTo Fail
    Set db = CurrentDb
    f = FreeFile
    sFile = UniqueFile(sFolder, "_database_properties", ".txt", "Database properties")
    Open sFile For Output As #f
    Print #f, "Database properties: " & CurrentProject.FullName
    Print #f, ""
    Print #f, "[CurrentDb.Properties]"
    PrintDaoProperties f, "  ", db.Properties
    Print #f, ""
    Print #f, "[CurrentProject.Properties]"
    On Error Resume Next    ' individual custom properties can raise on read
    For Each aop In CurrentProject.Properties
        sVal = ""
        sVal = CStr(aop.Value)
        Print #f, "  " & aop.Name & "=" & sVal
    Next aop
    On Error GoTo Fail
    Close #f
    ExportDbProperties = db.Properties.Count
    LogLine "OK    Database properties (" & ExportDbProperties & ") -> " & sFile
    Exit Function
Fail:
    LogFailure "Database properties", "_database_properties.txt"
    On Error Resume Next
    Close #f
    Kill sFile
End Function

'---------------------------------------------------------------------------
' Data: CSV for LOCAL tables only (linked MySQL tables are schema-only)
'---------------------------------------------------------------------------
Private Function ExportData(sFolder As String) As Long
    Dim db As DAO.Database, td As DAO.TableDef
    On Error GoTo Fail
    Set db = CurrentDb
    LogLine "SKIP  MSys system tables (includes any saved import/export specs and shared images)"
    For Each td In db.TableDefs
        If Not IsSkippableTable(td.Name) Then
            If Len(td.Connect) = 0 Then
                If ExportOneTableData(td, sFolder) Then
                    ExportData = ExportData + 1
                End If
            Else
                LogLine "SKIP  Data [" & td.Name & "] - linked table, schema only"
            End If
        End If
    Next td
    Exit Function
Fail:
    LogFailure "Data enumeration", "(object skipped)"
    Resume Next
End Function

Private Function ExportOneTableData(td As DAO.TableDef, sFolder As String) As Boolean
    Dim sFile As String
    On Error GoTo Fail
    sFile = UniqueFile(sFolder, SafeName(td.Name), ".csv", "Data [" & td.Name & "]")
    If HasComplexFields(td) Then
        ' TransferText fails outright on Attachment/multi-value columns;
        ' fall back to a manual writer that keeps the scalar columns.
        ExportOneTableData = ExportScalarCsv(td, sFile)
    Else
        DoCmd.TransferText TransferType:=acExportDelim, TableName:=td.Name, _
                           FileName:=sFile, HasFieldNames:=True
        LogLine "OK    Data [" & td.Name & "] -> " & sFile
        ExportOneTableData = True
    End If
    Exit Function
Fail:
    LogFailure "Data", td.Name
    On Error Resume Next
    Kill sFile                ' don't leave a partial file looking exported
End Function

Private Function HasComplexFields(td As DAO.TableDef) As Boolean
    Dim fld As DAO.Field
    For Each fld In td.Fields
        If fld.Type >= 101 Then    ' 101=Attachment, 102-109=multi-value
            HasComplexFields = True
            Exit Function
        End If
    Next fld
End Function

' Manual CSV fallback: scalar columns only, complex columns skipped with
' an explicit note in both the file and the log.  Dates are written as
' yyyy-mm-dd hh:nn:ss so the output is not regional-settings dependent.
Private Function ExportScalarCsv(td As DAO.TableDef, sFile As String) As Boolean
    Dim rs As DAO.Recordset, fld As DAO.Field
    Dim f As Integer, sLine As String, bFirst As Boolean
    On Error GoTo Fail
    f = FreeFile
    Open sFile For Output As #f
    For Each fld In td.Fields
        If fld.Type >= 101 Then
            Print #f, "# SKIP complex field: " & fld.Name & "  (" & DaoTypeName(fld.Type) & ")"
            LogLine "NOTE  Data [" & td.Name & "] complex field [" & fld.Name & "] not exportable to CSV"
        End If
    Next fld
    sLine = ""
    bFirst = True
    For Each fld In td.Fields
        If fld.Type < 101 Then
            If Not bFirst Then sLine = sLine & ","
            sLine = sLine & CsvCell(fld.Name)
            bFirst = False
        End If
    Next fld
    Print #f, sLine
    Set rs = CurrentDb.OpenRecordset("SELECT * FROM [" & td.Name & "]", dbOpenSnapshot)
    Do While Not rs.EOF
        sLine = ""
        bFirst = True
        For Each fld In td.Fields
            If fld.Type < 101 Then
                If Not bFirst Then sLine = sLine & ","
                sLine = sLine & CsvValue(rs.Fields(fld.Name))
                bFirst = False
            End If
        Next fld
        Print #f, sLine
        rs.MoveNext
    Loop
    rs.Close
    Close #f
    LogLine "OK    Data [" & td.Name & "] -> " & sFile & " (manual export, complex fields skipped)"
    ExportScalarCsv = True
    Exit Function
Fail:
    LogFailure "Data", td.Name
    On Error Resume Next
    rs.Close
    Close #f
    Kill sFile
End Function

Private Function CsvValue(fld As DAO.Field) As String
    If IsNull(fld.Value) Then
        CsvValue = ""
    ElseIf fld.Type = dbDate Then
        CsvValue = Format$(fld.Value, "yyyy-mm-dd hh:nn:ss")
    ElseIf fld.Type = dbLongBinary Or fld.Type = dbBinary Or fld.Type = dbVarBinary Then
        CsvValue = "(binary not exported)"
    Else
        CsvValue = CsvCell(CStr(fld.Value))
    End If
End Function

Private Function CsvCell(sVal As String) As String
    If InStr(sVal, ",") > 0 Or InStr(sVal, """") > 0 Or _
       InStr(sVal, vbCr) > 0 Or InStr(sVal, vbLf) > 0 Then
        CsvCell = """" & Replace$(sVal, """", """""") & """"
    Else
        CsvCell = sVal
    End If
End Function

'---------------------------------------------------------------------------
' Small shared helpers
'---------------------------------------------------------------------------
Private Function IsSkippableTable(sName As String) As Boolean
    IsSkippableTable = (Left$(sName, 4) = "MSys") Or (Left$(sName, 1) = "~")
End Function

Private Sub EnsureFolder(sPath As String)
    If Len(Dir$(sPath, vbDirectory)) = 0 Then MkDir sPath
End Sub

' Output subfolders are emptied each run so stale files from renamed or
' deleted objects (or partial writes from a failed run) cannot be
' committed as if they were current source.
Private Sub EnsureCleanFolder(sPath As String)
    EnsureFolder sPath
    On Error Resume Next
    Kill sPath & "*"
End Sub

' Replace anything outside A-Z a-z 0-9 space dash underscore period.
' Needed because object names like "Inventory Data Entry/Report" contain "/".
' The REAL object name is always recorded inside the file and in the log.
Private Function SafeName(sName As String) As String
    Dim i As Long, lCode As Long, sOut As String
    For i = 1 To Len(sName)
        lCode = AscW(Mid$(sName, i, 1))
        Select Case lCode
            Case 48 To 57, 65 To 90, 97 To 122, 32, 45, 46, 95
                sOut = sOut & Mid$(sName, i, 1)
            Case Else
                sOut = sOut & "_"
        End Select
    Next i
    SafeName = sOut
End Function

' SafeName can map two different object names to the same file name
' ("Badge#" and "Badge_" both become "Badge_").  Every output path is
' claimed here; on collision a numeric suffix is added and the remap is
' logged, so no export can silently overwrite another.
Private Function UniqueFile(sFolder As String, sBase As String, sExt As String, _
        sRealName As String) As String
    Dim sTry As String, n As Long
    If m_colUsedFiles Is Nothing Then Set m_colUsedFiles = New Collection
    sTry = sBase
    n = 1
    Do While IsUsedFile(sFolder & sTry & sExt)
        n = n + 1
        sTry = sBase & "_" & n
    Loop
    If n > 1 Then
        LogLine "NOTE  Name collision: " & sRealName & " -> " & sTry & sExt
    End If
    UniqueFile = sFolder & sTry & sExt
    m_colUsedFiles.Add UniqueFile, LCase$(UniqueFile)
End Function

Private Function IsUsedFile(sPath As String) As Boolean
    Dim sHit As String
    On Error Resume Next
    sHit = m_colUsedFiles(LCase$(sPath))
    IsUsedFile = (Err.Number = 0)
End Function

' Remove PWD=...; and PASSWORD=...; from ODBC connect strings.
Private Function ScrubPassword(sConn As String) As String
    ScrubPassword = RemoveToken(RemoveToken(sConn, "PASSWORD="), "PWD=")
End Function

Private Function RemoveToken(sConn As String, sToken As String) As String
    Dim s As String, p As Long, q As Long
    s = sConn
    p = InStr(1, s, sToken, vbTextCompare)
    Do While p > 0
        q = InStr(p, s, ";")
        If q = 0 Then
            s = Left$(s, p - 1) & "[password removed]"
        Else
            s = Left$(s, p - 1) & "[password removed]" & Mid$(s, q)
        End If
        p = InStr(1, s, sToken, vbTextCompare)
    Loop
    RemoveToken = s
End Function

Private Function DaoTypeName(lType As Long) As String
    Select Case lType
        Case dbBoolean:     DaoTypeName = "Boolean (Yes/No)"
        Case dbByte:        DaoTypeName = "Byte"
        Case dbInteger:     DaoTypeName = "Integer"
        Case dbLong:        DaoTypeName = "Long"
        Case dbCurrency:    DaoTypeName = "Currency"
        Case dbSingle:      DaoTypeName = "Single"
        Case dbDouble:      DaoTypeName = "Double"
        Case dbDate:        DaoTypeName = "Date/Time"
        Case dbBinary:      DaoTypeName = "Binary"
        Case dbText:        DaoTypeName = "Text"
        Case dbLongBinary:  DaoTypeName = "LongBinary (OLE Object)"
        Case dbMemo:        DaoTypeName = "Memo/Long Text"
        Case dbGUID:        DaoTypeName = "GUID"
        Case dbBigInt:      DaoTypeName = "BigInt"
        Case dbVarBinary:   DaoTypeName = "VarBinary"
        Case dbChar:        DaoTypeName = "Char"
        Case dbNumeric:     DaoTypeName = "Numeric"
        Case dbDecimal:     DaoTypeName = "Decimal"
        Case dbFloat:       DaoTypeName = "Float"
        Case dbTime:        DaoTypeName = "Time"
        Case dbTimeStamp:   DaoTypeName = "TimeStamp"
        Case 101:           DaoTypeName = "Attachment"   ' dbAttachment (ACE-only constant)
        Case 102 To 109:    DaoTypeName = "Complex/Multi-Value (" & lType & ")"
        Case Else:          DaoTypeName = "Unknown (" & lType & ")"
    End Select
End Function

' Each run starts with a fresh log so ERROR/OK lines from previous runs
' cannot be mistaken for the current run's result.
Private Sub ResetLog()
    On Error Resume Next
    Kill m_sLogPath
End Sub

' Open/Print/Close per call so no handle can be left open. Never fatal.
Private Sub LogLine(sText As String)
    On Error Resume Next
    Dim f As Integer
    f = FreeFile
    Open m_sLogPath For Append As #f
    Print #f, Format$(Now, "yyyy-mm-dd hh:nn:ss") & "  " & sText
    Close #f
End Sub

' Capture Err FIRST (before any other call can clear it), then log.
Private Sub LogFailure(sLabel As String, sRealName As String)
    Dim lNum As Long, sDesc As String
    lNum = Err.Number
    sDesc = Err.Description
    m_lErrors = m_lErrors + 1
    LogLine "ERROR " & sLabel & " [" & sRealName & "]  Err " & lNum & ": " & sDesc
End Sub
