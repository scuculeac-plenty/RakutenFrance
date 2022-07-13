# RakutenFrance Plugin

## Description
This plugin is full integration of Rakuten.fr marketplace to Plentymarkets environment. This plugin can synchronize order between systems.
Also automatically export inventory from Plentymarkets to Rakuten.fr marketplace.

## Features
### Assistant wizard
For this plugin configuration is done through assistant wizard. This plugin does not have UI anymore. Assistant wizard [documentation](https://developers.plentymarkets.com/dev-doc/assistant-documentation)

### API Documentation
Documentation for API can be found [here](https://developer.fr.shopping.rakuten.com/blog/documentation/).

### Account settings
Plugin adds configuration page in `Setup`->`Assistans`->`Rakuten.fr settings`\
Here You need to enter user data for API:
- `Username` - hashtagES
- `Token` - 2dc5c5e909ee4970a4d6963ea3a24e59


## Code Entry Points
- [LicenseController](./src/Controllers/LicenseController.php): Used for hearthbeat functionality
- [TestController](./src/Controllers/TestController.php): Used for plugin's DB manipulation

## Technical Information
### Background Information
Orders are imported using Plentymarkets cron functionality. Every 15 minutes plugin will call API and try to import new orders. Plugin uses a lot of `debug` and `info` level logs. To see them You need to enable it in the `Data`->`Log` page (for more see [Logging](#Logging)). 

### Event configuration
This plugin uses `Plentysystem` event system. To add new event go to `System`->`Orders`->`Events`. Press button `Add event procedure`. in dialog box name your configuration and select when this event should be fired, for example `Invoice generated`. Press `Save`. When modal closes, tick `Active` checkbox and in `Procedures` section add `Procedure` that should handle fired event. For example `Plugins`->`Rakuten.fr: Accept Order`. After that just save your configuration.

### Logging
To see Logs of Plentymarkets system navigate to `Data`->`Log` page. There You will see all the Logs of Plentymarkets system. Normally plugin will show only `error` level logs. Additionally You can enable `debug` level logs and this will show much more information of what is happening behind the scenes. To enable `debug` level logs, press `Configure logs` at the top-middle section of the `Log` page. In opened popup select `Rakuten.fr` plugin, check `Active` checkbox, select duration for how long this configuration should be active and select `Debug` from `Log level` list.


## External Resources
- Passwork Directory: [Priceminister](https://passwork.me/#!/p/5b17c36d6be78bd7198cbdfb)

### Installation Instructions
- Login to Plentymarkets backend
- Go to `Plugin`->`Git` and add new repository `https://gitlab.com/hashtagES/plugin-rakuten-france.git`
- Go to `Plugin` and in opened page select `Plugin set` you want to install plugin in
- In search pannel select `Git` and `Not intalled` checkboxes
- In plugins list select `Rakuten.fr` plugin and select install. When asked choose `3-orders-import-customer` branch
- Pull newest commits, active plugin and press `Save & publish plugins` button

### Test Cases
#### Create customer
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Contacts` page (CRM menu) and confirm that new customer was created.

#### Import Order
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Orders` page and confirm that new Order was created.

#### Accept Order items
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Create new event action for `RakutenFrance: Accept Order` procedure
3. Trigger event
4. Check Rakuten.fr backend to see if items were accepted
5. If items were not accepted there will be logs in plentymarkets system to indicate that

#### Refuse Order items
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Create new event action for `RakutenFrance: Refuse Order` procedure
3. Trigger event
4. Check Rakuten.fr backend to see if items were refused
5. If items were not refused there will be logs in plentymarkets system to indicate that

#### Changed Import Order
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Orders` page and confirm that new Order was created.
4. Navigate to `Orders` page and confirm that new customer was not created.

# RakutenFrance Plugin

## Description
This plugin is full integration of Rakuten.fr marketplace to Plentymarkets environment. This plugin can synchronize order between systems.
Also automatically export inventory from Plentymarkets to Rakuten.fr marketplace.

## Features
### Assistant wizard
For this plugin configuration is done through assistant wizard. This plugin does not have UI anymore. Assistant wizard [documentation](https://developers.plentymarkets.com/dev-doc/assistant-documentation)

### API Documentation
Documentation for API can be found [here](https://developer.fr.shopping.rakuten.com/blog/documentation/).

### Account settings
Plugin adds configuration page in `Setup`->`Assistans`->`Rakuten.fr settings`\
Here You need to enter user data for API:
- `Username` - hashtagES
- `Token` - 2dc5c5e909ee4970a4d6963ea3a24e59


## Code Entry Points
- [LicenseController](./src/Controllers/LicenseController.php): Used for hearthbeat functionality
- [TestController](./src/Controllers/TestController.php): Used for plugin's DB manipulation

## Technical Information
### Background Information
Orders are imported using Plentymarkets cron functionality. Every 15 minutes plugin will call API and try to import new orders. Plugin uses a lot of `debug` and `info` level logs. To see them You need to enable it in the `Data`->`Log` page (for more see [Logging](#Logging)). 

### Event configuration
This plugin uses `Plentysystem` event system. To add new event go to `System`->`Orders`->`Events`. Press button `Add event procedure`. in dialog box name your configuration and select when this event should be fired, for example `Invoice generated`. Press `Save`. When modal closes, tick `Active` checkbox and in `Procedures` section add `Procedure` that should handle fired event. For example `Plugins`->`Rakuten.fr: Accept Order`. After that just save your configuration.

### Logging
To see Logs of Plentymarkets system navigate to `Data`->`Log` page. There You will see all the Logs of Plentymarkets system. Normally plugin will show only `error` level logs. Additionally You can enable `debug` level logs and this will show much more information of what is happening behind the scenes. To enable `debug` level logs, press `Configure logs` at the top-middle section of the `Log` page. In opened popup select `Rakuten.fr` plugin, check `Active` checkbox, select duration for how long this configuration should be active and select `Debug` from `Log level` list.


## External Resources
- Passwork Directory: [Priceminister](https://passwork.me/#!/p/5b17c36d6be78bd7198cbdfb)

### Installation Instructions
- Login to Plentymarkets backend
- Go to `Plugin`->`Git` and add new repository `https://gitlab.com/hashtagES/plugin-rakuten-france.git`
- Go to `Plugin` and in opened page select `Plugin set` you want to install plugin in
- In search pannel select `Git` and `Not intalled` checkboxes
- In plugins list select `Rakuten.fr` plugin and select install. When asked choose `3-orders-import-customer` branch
- Pull newest commits, active plugin and press `Save & publish plugins` button

### Test Cases
#### Create customer
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Contacts` page (CRM menu) and confirm that new customer was created.

#### Import Order
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Orders` page and confirm that new Order was created.

#### Accept Order items
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Create new event action for `Rakuten.fr: Accept Order` procedure
3. Trigger event
4. Check Rakuten.fr backend to see if items were accepted
5. If items were not accepted there will be logs in plentymarkets system to indicate that

#### Refuse Order items
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Create new event action for `Rakuten.fr: Refuse Order` procedure
3. Trigger event
4. Check Rakuten.fr backend to see if items were refused
5. If items were not refused there will be logs in plentymarkets system to indicate that

#### Changed Import Order
1. Make sure that required API settings are entered and `Orders import` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=OrdersImportCron` route from Postman.
3. Navigate to `Orders` page and confirm that new Order was created.
4. Navigate to `Orders` page and confirm that new customer was not created.

#### Create catalog
1. Assistant `Setup`->`Assistans`->`Create catalog`.
2. Choose catalog type and catalog name.
3. if catalog was saved successfully, assistant should be saved without errors.
3. Check if catalog is loading `Data` -> `Catalogues` -> `{Your created catalog}`

#### Catalog export
1. Make sure that required API settings are entered and `Synchronize marketplace data` is activated in assistant wizard.
2. Call `{system_url}/rest/RakutenFrance/cron?model=exportAll` route from Postman.
3. Exports rakuten catalogs\
    3.1  Catalog must be active\
    3.1  Catalog must have required field filled.\
    3.1 Catalog must be 1 for each Alias/Category
 4. The exported catalog should be `Imported` to [Rakuten](https://sandbox.fr.shopping.rakuten.com/file?action=history)
 5. `ImportId` should be added at `CatalogHistory` table
 
 #### Catalog import data synchronization
 1. Follow `Catalog export` tests as it will require catalogs.
 2. Make sure that required API settings are entered and `Synchronize marketplace data` is activated in assistant wizard.
 3. Call `{system_url}/rest/RakutenFrance/cron?model=fileSync` route from Postman.
 4. Catalog imports will be processed\
    File processing:\
     4.1 Files status that are `Reçu`, `En attente`, `M.à.j. en cours` will be skipped.\
     4.2 Files status that are `Erreur`, `Annulé`, `Aucune ligne n’a été chargée` will be deleted and not processed.\
     4.3 Files status that are `Traite` will processed.\
     4.4 All other file statuses will be processed at before 3 time before deleting.\
    Product processing (variation SKU assigned for `Traite` statuses and Error notes for `Erreur`):   
     4.5 Navigate to `{Item}` -> `{Variation}`->`Availability`->`SKU` if successful SKU will be assigned.\
     4.6 Navigate to `{Item}` -> `{Variation}`->`Notes`  if Error, note will be displayed.\
