# Rakuten.fr Set-up documentation: 

Join the Rakuten Shopping Mall within 8 500 professional merchants and present your personalized shop to 10 million Club R members. Design your own Rakuten Corner, a space entirely dedicated to your products, and offer your clients 3 % in Rakuten Points for all your catalogue, entirely financed by Rakuten.



## Assistant : 
Rakuten.fr set-up is done by new plentymarkets Standard: assistant. After the successful installation of the plugin 4 assistants will be added automatically:

- Rakuten.fr settings.
- Rakuten.fr.fr Catalogue creation.
- Rakuten.fr and plentymarkets shipping matching

Assistants can be found under **Setup » Assistants »  Plugins »  {{name_of_plugin_set}}**.

## Rakuten.fr Settings assistant: 
This assistant has 4 steps: where you can set up a basic plugin configuration.

##### Step 1: Credentials
In this step, you need to enter your username and [token](https://ibb.co/N9s5Bzv) from [Rakuten.fr](https://ibb.co/HK8DwXy). 

#### Step 2: Order & Profile

##### Order Settings: 
Here you need to add a keyword, which will be used as an identification of the beginning of cancellation reason in the Order Note.
More information on how to work with this procedure can be found in the description of the Event Procedure: **“Rakuten.fr: Cancel order items”**.

_Example for CANCEL_REASON:_ <br>
In order to provide a cancelation reason, users need to create an Order Note on an order like the following: <br> 
CANCEL_REASON: _My test cancelation reason_. 
So when the event procedure for cancelation is triggered, everything after the key _CANCEL_REASON_ will be provided to Rakuten.fr as the cancellation reason. In this case: _My test cancelation reason_. 


##### Profile settings: FULLADVERT
Here you need to enter your profileID from Rakuten.fr. This ID is mandatory for the usage of EAN-Matching or the _Alias_ approach for inventory management. Your Rakuten.fr profile ID can be found in you Rakuten.fr account under **Inventaire » Envoyer mes fichiers de stock » Soumettre un nouveau fichier (?)**.
Please, use **WS_FULLADVERT** as value.


##### Profile settings : PRICEQUANTITY
Here you need to enter your Rakuten.fr profile ID. This ID is mandatory for stock synchronization and price updates and synchronization. Your Rakuten.fr profile ID can be found in you Rakuten.fr account under **Inventaire » Envoyer mes fichiers de stock » Soumettre un nouveau fichier (?)**.
Please, use **PRICEQUANTITY** as value.

##### Step 3: Account & Jobs
##### Account Settings. 
In your Account Settings you need to select an Account under which name the Order Notes will be created during the Order Management. Order Notes will be created if something went wrong, like a missing Item SKU, etc.

#####  Jobs Settings.
In this section, with toggle, you can on/off specific plugin functionality.
| Function | Description |
| --- | --- |
| Synchronize marketplace orders. (Every 15 minutes) | This functionality is responsible for order import from Rakuten.fr to plentymarkets. |
| Synchronize stock with the marketplace. (Every 15 minutes) | This functionality synchronizes the stock between plentymarkets and Rakuten.fr. <br>  The export will be included all variations: <br> - Which had stock changes in the last 30 minutes. <br> - Have SKU for Rakuten.fr Referrer. |
| Synchronize marketplace order statuses. (Every 20 minutes) | This functionality is responsible for the synchronization of the order status “Cancelled by Customer” and the order status “Ongoing Claim”. |
| Synchronize feed to the marketplace. (Every 1 hour) | This functionality is responsible for the synchronization of active Catalogues (done via _Alias_) to Rakuten.fr. |
|  Synchronize EAN matching file. (Every 1 hour) | This functionality is responsible for the synchronization of active Catalogues (done via _EAN-MATCHING_) to Rakuten.fr. |

#### Step 4: Client
In this section, you need to select the Client, which shall be used in the order import. This selection is important for inventory and warehouse management.

## Rakuten.fr Catalogue creation.
In this assistant, a new Catalogue template can get created by selecting specific alias of Rakuten.fr. Herefore you just need to add a name for a catalogue and select on of the aliases in the type selections.
After this assistant is finalized, a new Catalogue template for the alias you´ve selected will be created. 
All created Catalogues can be found under **Data->Catalogues**.

_**Note:** How to set up the catalogues will be written in another following section of this guide._


### Shipping profiles Matching : 

In this assistant you will haveto match your plentymarkets shipping profiles with the Rakuten.fr Carriers. This information is required in order to use the Event procedure : **"Rakuten.fr: Versandbestätigung übermitteln"**. 

So on Rakuten.fr orders will be marked as shipped with the shipping profile you matched on this assistant. 

On Rakuten.fr different shipping providers are available, which are listed [here](https://postimg.cc/YLwwZBHG). 

### Items management

In order to manage your items between plentymarkets and Rakuten.fr, you need to set up several things on your Variations, Warehouses and SalesPrice profiles.
Those are requirements for your Items to be included in the Catalogues (Alias and EAN-Matching).

The variation must have the referrer _Rakuten.fr_ in the market's list of variations.
To tell in which Catalogue the item is, a variation should be included. Therefore you need to add the Variation Property **Catalog : Rakuten.fr** and select a catalog template from the selection. 

_Note:_ Warehouses can be set at **Setup » Stoch » Warehouse**. <br>
Salesprices can be set at **Setup » Item » Salesprices**.

**That’s all!** only these two steps are mandatory for the item management with Catalogues. <br>


### Important
_**Note**: If for some reason variation doesn’t have the value, which is assigned in the Catalog template and marked as required, variations will be SKIPPED as well. 

All error messages for failed exports or validation errors of the catalogue of Rakuten.fr will be displayed here: **Einrichtung / Markets / Rakuten.fr / Catalogue Errors**.

Both types of catalogs (EAN and Alias) will be synchronized every hour.


### Requirements for Item stock synchronization:

The variation must have the referrer _Rakuten.fr_ in the market's list of variations.
The variation **MUST** have a Rakuten.fr SKU (it will be added automatically for new exported variations via plugin.) 

If you match already existing offers from Rakuten.fr with variations and use the stock synchronization, you need to manually add the SKU. In this case you must use [this value](https://postimg.cc/pyYDSQQy) from your Rakuten.fr as the SKU value.

_**Note:** The stock synchronization is triggered every 15 minutes and updating variations, which had stock changes in the last 30 minutes. So, if you want to update the stock of your variations from yesterday, you need to update stock of those variations first. After that the timestamp will be updated and variations will be included._ 

## Order management.

### Orders import.
The Order import is working every 15 minutes and importing orders from Rakuten.fr to plentymarkets. Orders with the status “REQUESTED ou REMINDED” will be taken. 
For correct variation to Order assignment, plugin is searching variation by SKU or by VariationId (in case you are using variationID as SKU identifier or Rakuten.fr). If the variation is missing or not found, the order will be created with missing variations. If the variations are missing, you will be informed about it in the order note!

### Event procedures:
The plugin will create 4 additional Event procedures on your plentymarkets system in order to help you manage orders from plentymarkets on Rakuten.fr.
- Rakuten.fr: Bestellung annehmen
- Rakuten.fr: Auftrag ablehnen
- Rakuten.fr: Versandbestätigung übermitteln
- Rakuten.fr: Auftragspositionen stornieren


#### Rakuten.fr: Bestellung annehmen (accept order items).
This event procedure will help you accept ordered items on Rakuten.fr, so that your customers will be able to provide payment and you can work on your shipment procedures. 
Also, after this event procedure is triggered, the customer's email and phone details will be provided and the shipping costs will be added to the order.

In order to accept one of several order items, you need to split the order in two delivery orders and accept one of two. <br> 
Further information about that matter can be found here: https://knowledge.plentymarkets.com/slp/orders/managing-orders#300


#### Rakuten.fr: Auftrag ablehnen (Refuse Order items)
With this event procedure you will be able to refuse ordered items on Rakuten.fr. Make sure, you are calling this event procedure at the right time. You can refuse an order while it’s still in the status _“REQUESTED ou REMINDED”_. Otherwise you will receive an error as an Order Note.

In order to refuse one of several ordered items, you need to split the order in two separate delivery orders and refuse one of the two orders. <br>
Further information about this matter can be found here: https://knowledge.plentymarkets.com/slp/orders/managing-orders#300


#### Rakuten.fr: Auftragspositionen stornieren (Cancel order)
With this event procedure you will be able to cancel an order, in case something went wrong after you accepted the order, but the customer already provided a payment.


#### Rakuten.fr: Versandbestätigung übermitteln (Shipping information).

This event procedure will let you provide shipping information of your Order from plentymarkets to Rakuten.fr. Please, make sure you are calling this event procedure after the tracking number was already created for the order.


## **FAQ:**
- If you already do have items on Rakuten.fr and want to synchronize them, please refer to the step **Item management** of this userguide.
- The following **error messages** are the most frequent to be displayed: <br>
  -Accept and Refuse Order Item even procedure:<br>
   _The parameter itemID is mandatory_ <br>
   _The parameter itemID is invalid_ <br>

  -Shipping information event procedure:<br>
   _Article xxxx : numéro incorrect_: Wrong item number.<br>
   _Article xxxx : a déjà été expédié_: The item has already been shipped.<br>
   _Article xxxx : statut incorrect. L’enregistrement de votre numéro de suivi n’est possible que si le statut de votre article est “confirmé”._: The tracking number cannot be transmitted as long as you don’t commit delivery.<br>

  -Cancel Item Event procedure: <br>
   _This item cannot be cancelled_ <br>
   _Your message cannot exceed 400 characters_
