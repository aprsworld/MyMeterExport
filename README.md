# MyMeterExport

There are 7 required arguments that need to be applied to myMeter15.php and 1 optional.

 * `tableName` - The names of the table(s) that will be queried for data. This must be in an array format.
 	
 * `station_id` - The station id(s) of the device(s). This is required to see if station is public. This must be in an array format.
 * `colName` - The name(s) of the column(s) to be queried from the table(s). This must be in an array format.
 * `meterNumber` - Unique identifier(s) for device(s). This must be in an array format.
 * `readingType` - 1 if it is a metered value, otherwise 2. This must be in an array format.
 * `quality` - 1 if actual values, 2 if estimated values.  This must be in an array format.
 * `startDate` - The date for the data you want to receive. The date must be in Y-m-d format
 	- ex) startDate=2015-01-01
 * `tzOffset` - Optional, this allows you to apply a timezone offset to the data
 ###URL Array example
  `?tableName%5B%5D=exampleTable_E1234&tableName%5B%5D=exampleTable_E5678`
 
 ###NOTE:
 Please make sure that all arrays are the same size and that the data pertaining to a station is found on the same index for all arrays
