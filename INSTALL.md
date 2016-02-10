1. Download module

2. Copy exfeatures folder content in modules/ directory

3. Go to Prestashop Admin Panel -> Modules -> Front Office Features -> exfeatures -> install

4. During to install walk throught next steps:
> Creating table _DB\_PREFIX\_feature\_group_

> Creating table _DB\_PREFIX\_feature\_group\_lang_

> Adding field ‘id\_group’ to table _DB\_PREFIX\_feature_

> Adding table ‘position’ to table _DB\_PREFIX\_feature_

> Updating _DB\_PREFIX_.tab table – class of tab ‘Features’ change from                                                 AdminFeatures to AdminExFetures from exfetures module

> Update _DB\_PREFIX_.tab table – class of tab Catalog change from     AdminCatalog to AdminCatalogExFeatures from exfetures module.

> Tabs replacement is one the same, you can do in Employees -> Tabs.
Intall script of this module do this automatically for you
> Patch core file 

&lt;adminFolder&gt;

/tabs/AdminCatalog.php
Do not very worry about this – everything what have been changed is visible of $adminProduct property of AdminCatalog class from private to protected.
> Tests was show, that such replacement not damage system.

> Copy ajax\_ex\_features.php to 

&lt;admin&gt;

 folder

> Copy Product.php to /override/classes/_Product.php_

> Patch default (prestashop) theme for display grouped products.

> File, which should be patched: themes/prestashop/product.tpl.

> Please, make backups of this files before installing module.

> Create default group.

After installation module, you can see only features, which belong to one of existing groups.
That’s why during installation was created one group with name ‘Common’ and all features, which exists in system moved in this group.
Everything of this you can see into exfaetures.php file in exfeatures::install() function.
Please click ‘configure’ to check whether or not module install properly