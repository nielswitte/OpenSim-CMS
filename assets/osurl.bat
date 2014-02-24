@echo off > osurl.bat
SET rawUrl=%1
SET cleanUrl=%rawUrl:~10%
START "OpenSim" /HIGH /B "%~d0%~p0SingularityViewer.exe" -loginpage %cleanUrl%?method=login -loginuri %cleanUrl%
EXIT