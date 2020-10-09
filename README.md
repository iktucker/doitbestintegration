# doitbestintegration

## About

This is a module for Thirybees (Prestashop?) that integrates the inventory count API and EDI files from Do It Best's dataxchange platform to allow your shop to automatically update itself at set times.

## Usage

Download and extract the contents of this repository to your modules directory in ecommerce. On the "Modules and Services" menu, locate this plugin under "Shipping and Logistics". Click the Install button.

Once the plugin installs, click the "Configure" button. This page allows you to set information about your dataxchange account and set up how often you'd like your shop to fetch inventory updates.

### Configuration Options

### API Key

This is the common key that is provided when you subscribe to API-type updates on dataxchange

### Store Number

This is your Do It Best store number. This is generally a 4-digit number.

### DIB Warehouse

Currently, the tool only loads data for your primary warehouse. This is slightly more performant and also helps keep shipping times reasonable. Please create an issue if you want the ability to load from any warehouse.

Select the nearest warehouse to you from the dropdown.

### EDI FTP Host

This is the EDI file repository for your data. This should be a Do It Best-hosted FTPS server. The format for this field is hostname:port. I obtained information about this from my ERP software's "Do it Best Telecommunications" menu, specifically the "Maintain Transmission Control Streams" option.

### EDI FTP Username

The FTP username for your EDI file repo

### EDI FTP Password

The FTP password for the EDI file repo
