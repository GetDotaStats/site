@ECHO OFF
cd landing
FOR %%f IN (*) DO dos2unix %%f
@PAUSE