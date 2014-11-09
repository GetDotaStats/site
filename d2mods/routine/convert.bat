@ECHO OFF
cd php
FOR %%f IN (*) DO dos2unix %%f
cd ../scripts
FOR %%f IN (*) DO dos2unix %%f
@PAUSE