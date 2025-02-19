# Changelog
- All notable changes to this project will be documented in this file.
- See [changelog structure](https://keepachangelog.com/en/0.3.0/) for more information of how to write perfect changelog.

## Release note
- Make sure what version is required for the client. Is it production or testing
- Make sure why developing, set DISABLE_CACHE to true in order for dependency injection loaded containers would change.
  Otherwise, they are in immutable state.
- When providing the zip , make sure there are no .git or var folder
- Install vendors using composer install --no-dev --optimize-autoloader
- Use existing vendor if exists in repository for compatability issues


## [3.1.1] - 2021-01-11

### Changed
- prestashop 1.7.7 controllers created for printing service
- order view page compatibility update, parameters changes, new hooks instantiated
- order add page new parameters added, logic changed for version compliance
- javascript changes for prestashop 1.7.7
- bootstrap update to version 4 in backoffice pages

## [3.1.2] - 2021-01-22

### Changed
- Show prestashop version in shipment reference

## [3.1.3] - 2021-01-29

### Changed
- Validations added for back office address form
- Functionality added, show API error messages at front end

## [3.1.4] - 2021-02-25

### Changed
- Validation added for back office product page on deleted carriers(do not show deleted carrier)
- Name changed for back office terminal import string
- Reference 4 for shipments string format changed
- Set parcels from different countries functionality added
- Bugfix when no idcart in order page to select parcel

## [3.1.5] - 2021-03-25
### Changed
- Added shipping cost to dpd shipment
- Translations added for carriers on webservice change
- Module tab install/uninstall dublication on module tab titles, all invisible titles are showing bug fixes added
- Maps loading sequence fixed

## [3.1.6] - 2021-06-02
### Changed
- Pudo service weights changed, Portugal and other countries added
- Bugfix for old prestashop versions when smarty does not read function
- Bugfix on tab fix for old prestashop versions
- Feature added, calculate parcel weight by distribution
- Show parcel terminal even tough delivery city is not correct
- Carrier translation update
- Vendor zip updated, port removed from api
- Map fix, do not load map script when off
- Bugfix map does not load when address is changed

## [3.2.7] - 2021-10-27
### Changed
- Import parcels function changed on cron parcel update, take countries only from zone range functionality added.
- Shipment price bug fix when product customizations are used
- Functionality to regenerate prestashop carriers in backoffice
- Functionality to send email with parcel tracking links on shipment creation
- Bugfix, when on first load parcel is not selected.
- Bugfix, when carrier cannot be changed in back office order view and label is not printed
- Bugfix, when order is using voucher, recalculate parcel total to match order total(shipping discount excluded)
- Bugfix, mobile view, bad styles fixed
- Beta version on compatability with SuperCheckout module on prestashop177
- Render Shipping price even though customer is not logged in functionality added
- Allow select pudo points if customer is missing(not logged in user)
- Render select with search(jquery choosen lib) on delivery method select(supercheckout applicable)
- Setup logic to save phone number dynamically(on ajax event trigger), prevents missing phone number in specific cases
- Validations added for phone number, while saving
- Format phone number while saving(removes phone prefix while saving phone number example +370123456789 turns to 123456789 on input field)
- Initiate logic to handle parcel terminals without address added on prestashopsrc/Service/Parcel/ParcelUpdateService.php
- Ajax added to validate missing pudo or phone number on supercheckout module
- CSS fixes for phone inputs and mobile view
- Feature change, remove cache from symfony container compilation(fixes problems for clients which does not have permissions for folder write inside module)
- Print label changes on old prestashop versions(do not open new tab when download option selected)
- Shipment creation and label print rewrok for prestashop versions above 177, use of symfony routing to trigger label service
- DPD log message improvements on shipment creation, log errors while creating shipment and printing labels
- Missing pudo ID fix, when parcel is not reselected in frontend
- Parcel tracking email template changed, URL changes, show only parcel number in tracking email template
- Bugfix, when in parcel validation function wrong argument is set, carrier ID instead of reference.
- Bugfix, override in array method in javascript, as different jquery version act differently while executing function

## [3.2.8] - 2021-12-08
### Changed
- Bugfix when combination with price impact calculates wrong price on shipment.
- Bugfix when prestashop versions below 1704 loses object instance and causes an error while importing parcels.
- Bugfix when prestashop versions below 1704 smarty variable is not read correctly.
- Bugfix when prestashop versions below 1704 tries to load undefined method.
- More precision calculation library added, vendor regenerated, zip for vendor updated
- Compatability fix for old php versions, do not use pipes in try catch statements
- Bugfix when parcel terminal form is always visible in order
- Bugfix when parcel terminal unconditionally changed to first selection while searching.
- Upgrade added to avoid error when client upgrades modules and does not delete cache.
- Bugfix when parcel tracking validation was called from prestashop core. Fall back to universal legacy function
- Logic to execute code further if error occurs on tracking email send.
- Bugfix when parcel terminal form is always visible in order
- Translatable string added when no option available, translations updated for all baltic languages for pickup select.
- Compatability for shipments from: GB,IE,NL added, service for different post code formatting initiated.

## [3.2.9] - 2021-06-09
- Restriction for COD carriers while using LATVIAN webservice removed
- ZIP folder inside repository removed
- Bugfix in cart, if prestashop default carrier is selected dpd-phone input for phone number is no longer required'.
- Translation changed in Import Pick up points
- Sustainable logo added to DPD carrier text
- API endpoint changed in dpdbalticsapi, logic improved, no to send unnecessary params
- Remark message upgrade added, allow special chars, trim content to prevent error.

## [3.2.10]
 - Validation message styles unified with super checkout module
 - Validation message handling added(remove change and display correct messages)
 - Logic to execute superchekout handling added only when correct carrier selected
 - Parcel return label logic added to Admin Order View
 - Automated parcel return settings added to Shipment settings
 - On Automated parcel setting return Shipment and Return labels are printed together

## [3.2.11]
- Sustainable logo added to DPD carrier text for older versions of theme

## [3.2.12]
- Print return label on initial order creation in bulk action and save&print bug fix

## [3.2.13]
- Improved installation and uninstallation.
- Improved shipping price calculation.
- Improved performance of BO orders page when many shipments have been created.
- Improved label printing functionality.
- Implemented checkout to use selected carrier instead of first one on cart.
- Implemented usage of PUDO address as return label address when PUDO was selected during checkout.
- Implemented creation of missing DPD shipment when saving label.
- Improved order message functionality when new lines were passed.

## [3.2.14]
- Fixed module price rule and address tabs not opening.

## [3.2.15]
- Added possibility to change carrier for shipment from BO Order page before printing label even if Carrier is inactive in module.
- Shipping tab improvements.
- Improved PUDO orders with incorrect post code.
- Fixed BO bug then changing terminal.
- Improved module code base.

## [3.2.16]
- Improved performance
- Added additional validation

## [3.2.17]
- Label printing after changing shipment product bug fix

## [3.2.18]
- Ability to hide label print feature in orders list page
- Vulnerability fix with development dependencies
- Price rule bug fix to add additional filtration by country

## [3.2.19]
- Carrier availability in country 
- Phone input selections sorted by active countries in shop
- Work hours pop up fix
- Numeric post code improvements

## [3.2.20]
- One page checkout compatibility improvements
- Multiple payment transaction and total price fix

## [3.2.21]
- Fixed label printing error and 0 goods price displaying
- Improved price calculation
- Fixed issues with carrier shown as "Free" when it is not needed to display
- Carriers error handling improvements

## [3.2.22]
- Removed PUDO default preselection