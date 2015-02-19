# MyMeterExport

There are 8 required arguments that need to be applied to myMeter15.php and 1 optional.

 * `tableName` - The names of the table(s) that will be queried for data. This must be in an array format.
 	
 * `station_id` - The station id(s) of the device(s). This is required to see if station is public. This must be in an array format.
 * `colName` - The name(s) of the column(s) to be queried from the table(s). This must be in an array format.
 * `meterNumber` - Unique identifier(s) for device(s). This must be in an array format.
 * `readingType` - 1 if it is a metered value, otherwise 2. This must be in an array format.
 * `quality` - 1 if actual values, 2 if estimated values.  This must be in an array format.
 * `startDate` - The date for the data you want to receive. The date must be in Y-m-d format
 	- ex) startDate=2015-01-01
 * `scaleFactor` - used to scale the value if necessary, such as converting watts to kW. 
 * `tzOffset` - Optional, this allows you to apply a timezone offset to the data
 ###URL Array example
  `?tableName%5B%5D=exampleTable_E1234&tableName%5B%5D=exampleTable_E5678`
 
 ###NOTE:
 Please make sure that all arrays are the same size and that the data pertaining to a station is found on the same index for all arrays


#Rewrite rule for mod_rewrite

###Notes:
 * The serial numbers used in this example are do not exist. Change E1234 to you ps2tap station_id and E4567_52256 to your wattnode station_id
 * The rewrite rule is written for a local server, but forwards to ian.aprsworld.com. The rest of the url should not show up if the rewrite rule is on the same network as myMeter15.php
 * To get a csv file for a specific date, `Feb 02 2015` for example, add the date to the end of the localhost directory: `localhost/2015-02-02`
 * The date has to be in `YYYY-MM-DD` format

```
<Directory /var/www/html>
        Options Indexes FollowSymLinks Multiviews
        AllowOverride AuthConfig Options
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^([^/\.]+)/?$ http://ian.aprsworld.com/myMeter/myMeter15.php?tzOff=0&startDate=$1&tableName[]=ps2tap_E1234&tableName[]=ps2tap_E1234&tableName[]=wnc_basic_E4567_52256&tableName[]=wnc_basic_E4567_52256&colName[]=energy_produced&colName[]=output_power&colName[]=energySumNR&colName[]=powerSum&readingType[]=1&readingType[]=2&readingType[]=1&readingType[]=2&quality[]=1&quality[]=1&quality[]=1&quality[]=1&meterNumber[]=wind0&meterNumber[]=wind0&meterNumber[]=solar0&meterNumber[]=solar0&scaleFactor[]=1&scaleFactor[]=.001&scaleFactor[]=1&scaleFactor[]=.001 [L]
</Directory>
```

