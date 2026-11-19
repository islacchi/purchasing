' hidden.vbs — runs the given command line in a completely windowless way.
'
' Used by the Windows Task Scheduler task that keeps `php artisan
' schedule:run` ticking, and by the deadline-toast launcher, to avoid any
' console flash. wscript.exe is a GUI process — no console ever exists.
'
' Every argument is joined into the child command line unchanged. A segment
' that contains a space is quoted automatically, so paths with spaces work.
'
' Exit code = the child's exit code.

Dim sh, i, cmd
Set sh = CreateObject("WScript.Shell")

cmd = ""
For i = 0 To WScript.Arguments.Count - 1
    If i > 0 Then cmd = cmd & " "
    Dim arg
    arg = WScript.Arguments(i)
    If InStr(arg, " ") > 0 Then arg = Chr(34) & arg & Chr(34)
    cmd = cmd & arg
Next

WScript.Quit sh.Run(cmd, 0, True)
