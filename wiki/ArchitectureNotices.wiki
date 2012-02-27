Architecture notices
During installation module, we copy file Product.php to override/classes/ folder. This script contain class Product, which extends ProductCore class from classes/Product.phpIn class Product has overrided method getFetatures, which return features grouped in groups.

During installation patche file theme/prestashop/product.tpl, replace piece of code, which display features on piece of code, which take into consideration groups in $features array.

Functional for working with features contains in file AdminFeatures class.Module contain class AdminExFeatures for adding functional for working with groups too. Methods of this class calls because this class become class of tab ‘Features’ (see exfeatures::install())

Because I want display groups of features in product editing form I also extend AdminCatalog class by AdminCatalogExFeatures class. In this class I have overrided constructor, where property $adminProducts set in instance of AdminProductsExFetures class. This class extends AdminProducts class, overrides displayFormFeatures method for displaying features by groups, using tabs like accordion.