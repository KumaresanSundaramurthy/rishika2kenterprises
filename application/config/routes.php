<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'launch';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Public receipt page (no login required)
$route['receipt/(:any)'] = 'receipt/index/$1';

// Public document viewer — Invoice, Purchase, PO, SO, SR, PR, Quotation, etc.
// PDF must be listed before the generic wildcard so doc/pdf/TOKEN routes correctly.
$route['doc/pdf/(:any)'] = 'doc/pdf/$1';
$route['doc/(:any)']     = 'doc/view/$1';
$route['invoice/(:any)'] = 'doc/view/$1';   // alias — matches email share links

// Cache API (authenticated)
$route['cache/get']     = 'cache/get';
$route['cache/set']     = 'cache/set';
$route['cache/delete']  = 'cache/delete';
$route['cache/refresh'] = 'cache/refresh';

// Cache Monitor — super admin + dev password only
$route['dev/cache']                    = 'cachemonitor/index';
$route['dev/cache/verifyPassword']     = 'cachemonitor/verifyPassword';
$route['dev/cache/getRedisData']       = 'cachemonitor/getRedisData';
$route['dev/cache/getUpstashData']     = 'cachemonitor/getUpstashData';
$route['dev/cache/deleteRedisKey']     = 'cachemonitor/deleteRedisKey';
$route['dev/cache/deleteUpstashKey']   = 'cachemonitor/deleteUpstashKey';

// Login
$route['portal'] = 'login/login';
$route['logout'] = 'login/logout';

// Social OAuth
$route['auth/google']             = 'oauth/googleRedirect';
$route['auth/google/callback']    = 'oauth/googleCallback';
$route['auth/facebook']           = 'oauth/facebookRedirect';
$route['auth/facebook/callback']  = 'oauth/facebookCallback';
$route['launch/sendEnquiry'] = 'launch/sendEnquiry';

// Forgot / Reset password (public)
$route['forgot-password']          = 'login/forgotPassword';
$route['forgot-password/send']     = 'login/sendResetLink';
$route['reset-password/update']    = 'login/doForgotReset';
$route['reset-password/(:any)']    = 'login/showResetForm/$1';

// Settings
$route['settings/profile']                     = 'profile';
$route['settings/profile/getSignaturesJson']    = 'profile/getSignaturesJson';
$route['settings/profile/getSignatureList']     = 'profile/getSignatureList';
$route['settings/profile/saveSignature']        = 'profile/saveSignature';
$route['settings/profile/updateSignature']      = 'profile/updateSignature';
$route['settings/profile/deleteSignature']      = 'profile/deleteSignature';
$route['settings/profile/setDefaultSignature']   = 'profile/setDefaultSignature';
$route['settings/profile/saveProfileAddress']    = 'profile/saveProfileAddress';
$route['settings/profile/saveProfileWorkInfo']   = 'profile/saveProfileWorkInfo';
$route['settings/profile/saveProfileAttachment']   = 'profile/saveProfileAttachment';
$route['settings/profile/deleteProfileAttachment'] = 'profile/deleteProfileAttachment';
$route['settings/profile/getEduExp']               = 'profile/getEduExp';
$route['settings/profile/saveEducation']           = 'profile/saveEducation';
$route['settings/profile/deleteEducation']         = 'profile/deleteEducation';
$route['settings/profile/saveExperience']            = 'profile/saveExperience';
$route['settings/profile/deleteExperience']          = 'profile/deleteExperience';
$route['settings/profile/getBankDetails']             = 'profile/getBankDetails';
$route['settings/profile/saveBankDetails']            = 'profile/saveBankDetails';
$route['settings/profile/getEmergencyContacts']      = 'profile/getEmergencyContacts';
$route['settings/profile/saveEmergencyContact']      = 'profile/saveEmergencyContact';
$route['settings/profile/deleteEmergencyContact']    = 'profile/deleteEmergencyContact';
$route['settings/profile/setPrimaryContact']         = 'profile/setPrimaryContact';
$route['settings/profile/getExpenses']                  = 'profile/getExpenses';
$route['settings/profile/saveExpense']                  = 'profile/saveExpense';
$route['settings/profile/deleteExpense']                = 'profile/deleteExpense';
$route['settings/profile/getExpenseAttachments']        = 'profile/getExpenseAttachments';
$route['settings/profile/saveExpenseAttachment']        = 'profile/saveExpenseAttachment';
$route['settings/profile/deleteExpenseAttachment']      = 'profile/deleteExpenseAttachment';
$route['settings/organisation']                          = 'organisation';

// ── Accounting ────────────────────────────────────────────────────────────
$route['accounting/chartofaccounts']                     = 'accounting/chartofaccounts';
$route['accounting/getChartOfAccountsPage/(:num)']       = 'accounting/getChartOfAccountsPage/$1';
$route['accounting/saveLedger']                          = 'accounting/saveLedger';
$route['accounting/toggleLedgerStatus']                  = 'accounting/toggleLedgerStatus';
$route['accounting/deleteLedger']                        = 'accounting/deleteLedger';
$route['accounting/generalledger']                       = 'accounting/generalledger';
$route['accounting/getLedgerStatementAjax']              = 'accounting/getLedgerStatementAjax';
$route['accounting/journallist']                         = 'accounting/journallist';
$route['accounting/getJournalListPage/(:num)']           = 'accounting/getJournalListPage/$1';
$route['accounting/getJournalDetail']                    = 'accounting/getJournalDetail';
$route['accounting/trialbalance']                        = 'accounting/trialbalance';
$route['accounting/getTrialBalanceAjax']                 = 'accounting/getTrialBalanceAjax';
$route['setpassword/submit']   = 'setpassword/submit';
$route['setpassword/(:any)']   = 'setpassword/index/$1';
$route['setpassword']          = 'setpassword/index';

$route['settings/users']                              = 'users';
$route['settings/users/getPageDetails/(:num)']        = 'users/getPageDetails/$1';
$route['settings/users/getPageDetails']               = 'users/getPageDetails';
$route['settings/users/getUserDetail']                = 'users/getUserDetail';
$route['settings/users/toggleStatus']                 = 'users/toggleStatus';
$route['settings/users/saveUser']                     = 'users/saveUser';
$route['settings/users/getUserAttachments']           = 'users/getUserAttachments';
$route['settings/users/saveUserAttachment']           = 'users/saveUserAttachment';
$route['settings/users/deleteUserAttachment']         = 'users/deleteUserAttachment';
$route['users/getOrgUsers']                           = 'users/getOrgUsers';
$route['users/getEmployeeList']                       = 'users/getEmployeeList';
$route['settings/generalsettings']           = 'settings/generalsettings';
$route['settings/updateGeneralSettings']     = 'settings/updateGeneralSettings';
$route['settings/updateProductSettings']     = 'settings/updateProductSettings';
$route['settings/thermalconfig']             = 'settings/thermalconfig';
$route['settings/banks']                  = 'settings/banks';
$route['settings/msgtemplates']           = 'settings/msgtemplates';
$route['settings/getThermalConfigList']   = 'settings/getThermalConfigList';
$route['settings/saveThermalConfig']      = 'settings/saveThermalConfig';
$route['settings/deleteThermalConfig']    = 'settings/deleteThermalConfig';
$route['settings/getBankList']            = 'settings/getBankList';
$route['settings/getBankBalance']         = 'settings/getBankBalance';
$route['settings/getBankDetail']          = 'settings/getBankDetail';
$route['settings/saveBankDetail']         = 'settings/saveBankDetail';
$route['settings/deleteBankDetail']       = 'settings/deleteBankDetail';
$route['settings/setDefaultBank']         = 'settings/setDefaultBank';
$route['settings/transferFunds']          = 'settings/transferFunds';
$route['settings/getMsgTemplateList']     = 'settings/getMsgTemplateList';
$route['settings/getMsgTemplateDetail']   = 'settings/getMsgTemplateDetail';
$route['settings/saveMsgTemplate']        = 'settings/saveMsgTemplate';
$route['settings/deleteMsgTemplate']      = 'settings/deleteMsgTemplate';

// Roles
$route['settings/roles']                         = 'roles/index';
$route['settings/roles/getRolesList']            = 'roles/getRolesList';
$route['settings/roles/getRolePermissions']      = 'roles/getRolePermissions';
$route['settings/roles/saveRole']                = 'roles/saveRole';
$route['settings/roles/saveRolePermissions']     = 'roles/saveRolePermissions';
$route['settings/roles/deleteRole']              = 'roles/deleteRole';
$route['settings/roles/refreshTokens']           = 'roles/refreshTokens';

// Pro Forma Invoices
$route['proforma']                                                 = 'proformainvoices/index';
$route['proforma/create']                                          = 'proformainvoices/create';
$route['proforma/(:num)/edit']                                     = 'proformainvoices/edit/$1';
$route['proforma/getPageDetails/(:num)']                           = 'proformainvoices/getPageDetails/$1';
$route['proforma/getPageDetails']                                  = 'proformainvoices/getPageDetails';
$route['proforma/addProFormaInvoice']                              = 'proformainvoices/addProFormaInvoice';
$route['proforma/updateProFormaInvoice']                           = 'proformainvoices/updateProFormaInvoice';
$route['proforma/deleteProFormaInvoice']                           = 'proformainvoices/deleteProFormaInvoice';
$route['proforma/duplicateProFormaInvoice']                        = 'proformainvoices/duplicateProFormaInvoice';
$route['proforma/updateProFormaStatus']                            = 'proformainvoices/updateProFormaStatus';
$route['proforma/convertProFormaToInvoice']                        = 'proformainvoices/convertProFormaToInvoice';
$route['proforma/getProFormaDetail']                               = 'proformainvoices/getProFormaDetail';

// Delivery Challans
$route['deliverychallan']                                          = 'deliverychallans/index';
$route['deliverychallan/create']                                   = 'deliverychallans/create';
$route['deliverychallan/(:num)/edit']                              = 'deliverychallans/edit/$1';
$route['deliverychallan/getPageDetails/(:num)']                    = 'deliverychallans/getPageDetails/$1';
$route['deliverychallan/getPageDetails']                           = 'deliverychallans/getPageDetails';
$route['deliverychallan/addDeliveryChallan']                       = 'deliverychallans/addDeliveryChallan';
$route['deliverychallan/updateDeliveryChallan']                    = 'deliverychallans/updateDeliveryChallan';
$route['deliverychallan/deleteDeliveryChallan']                    = 'deliverychallans/deleteDeliveryChallan';
$route['deliverychallan/duplicateDeliveryChallan']                 = 'deliverychallans/duplicateDeliveryChallan';
$route['deliverychallan/updateDeliveryChallanStatus']              = 'deliverychallans/updateDeliveryChallanStatus';
$route['deliverychallan/getChallanDetail']                         = 'deliverychallans/getChallanDetail';
$route['deliverychallan/convertChallanToInvoice']                  = 'deliverychallans/convertChallanToInvoice';
$route['deliverychallan/packingList/(:num)']                       = 'deliverychallans/packingList/$1';

// Expenses
$route['expenses']                                                 = 'expenses/index';
$route['expenses/getPageDetails/(:num)']                           = 'expenses/getPageDetails/$1';
$route['expenses/getPageDetails']                                  = 'expenses/getPageDetails';
$route['expenses/addExpense']                                      = 'expenses/addExpense';
$route['expenses/updateExpense']                                   = 'expenses/updateExpense';
$route['expenses/deleteExpense']                                   = 'expenses/deleteExpense';
$route['expenses/updateExpenseStatus']                             = 'expenses/updateExpenseStatus';
$route['expenses/getExpenseDetail']                                = 'expenses/getExpenseDetail';
$route['expenses/getCategories']                                   = 'expenses/getCategories';
$route['expenses/addCategory']                                     = 'expenses/addCategory';
$route['expenses/getCategoryList']                                 = 'expenses/getCategoryList';
$route['expenses/getCategoryList/(:num)']                          = 'expenses/getCategoryList/$1';
$route['expenses/updateCategory']                                  = 'expenses/updateCategory';
$route['expenses/deleteCategory']                                  = 'expenses/deleteCategory';
$route['expenses/duplicateExpense']                                = 'expenses/duplicateExpense';
$route['expenses/recordPayment']                                   = 'expenses/recordPayment';
$route['expenses/getPaymentHistory']                               = 'expenses/getPaymentHistory';
$route['expenses/getPaymentAttachments']                           = 'expenses/getPaymentAttachments';
$route['expenses/getAttachments']                                  = 'expenses/getAttachments';

// Indirect Income
$route['indirectincome']                                           = 'indirectincome/index';
$route['indirectincome/getPageDetails/(:num)']                     = 'indirectincome/getPageDetails/$1';
$route['indirectincome/getPageDetails']                            = 'indirectincome/getPageDetails';
$route['indirectincome/addIncome']                                 = 'indirectincome/addIncome';
$route['indirectincome/updateIncome']                              = 'indirectincome/updateIncome';
$route['indirectincome/deleteIncome']                              = 'indirectincome/deleteIncome';
$route['indirectincome/updateIncomeStatus']                        = 'indirectincome/updateIncomeStatus';
$route['indirectincome/getIncomeDetail']                           = 'indirectincome/getIncomeDetail';
$route['indirectincome/getCategories']                             = 'indirectincome/getCategories';
$route['indirectincome/addCategory']                               = 'indirectincome/addCategory';
$route['indirectincome/getCategoryList']                           = 'indirectincome/getCategoryList';
$route['indirectincome/getCategoryList/(:num)']                    = 'indirectincome/getCategoryList/$1';
$route['indirectincome/updateCategory']                            = 'indirectincome/updateCategory';
$route['indirectincome/deleteCategory']                            = 'indirectincome/deleteCategory';
$route['indirectincome/duplicateIncome']                           = 'indirectincome/duplicateIncome';
$route['indirectincome/recordPayment']                             = 'indirectincome/recordPayment';
$route['indirectincome/getPaymentHistory']                         = 'indirectincome/getPaymentHistory';
$route['indirectincome/getPaymentAttachments']                     = 'indirectincome/getPaymentAttachments';
$route['indirectincome/getAttachments']                            = 'indirectincome/getAttachments';

// Sales Orders
$route['salesorders']                                      = 'salesorders/index';
$route['salesorders/create']                               = 'salesorders/create';
$route['salesorders/(:num)/edit']                          = 'salesorders/edit/$1';
$route['salesorders/getSalesOrdersPageDetails/(:num)']     = 'salesorders/getSalesOrdersPageDetails/$1';
$route['salesorders/getSalesOrdersPageDetails']            = 'salesorders/getSalesOrdersPageDetails';
$route['salesorders/addSalesOrder']                        = 'salesorders/addSalesOrder';
$route['salesorders/updateSalesOrder']                     = 'salesorders/updateSalesOrder';
$route['salesorders/deleteSalesOrder']                     = 'salesorders/deleteSalesOrder';
$route['salesorders/duplicateSalesOrder']                  = 'salesorders/duplicateSalesOrder';
$route['salesorders/convertSalesOrderToInvoice']           = 'salesorders/convertSalesOrderToInvoice';
$route['salesorders/convertSalesOrderToDeliveryChallan']    = 'salesorders/convertSalesOrderToDeliveryChallan';

// Transactions shared
$route['transactions/searchVendors']       = 'transactions/searchVendors';
$route['salesorders/updateSalesOrderStatus']               = 'salesorders/updateSalesOrderStatus';
$route['salesorders/getSalesOrderDetail']                  = 'salesorders/getSalesOrderDetail';

// Invoices
$route['invoices']                                         = 'invoices/index';
$route['invoices/create']                                  = 'invoices/create';
$route['invoices/(:any)/edit']                             = 'invoices/edit/$1';
$route['invoices/getInvoicesPageDetails/(:num)']           = 'invoices/getInvoicesPageDetails/$1';
$route['invoices/getInvoicesPageDetails']                  = 'invoices/getInvoicesPageDetails';
$route['invoices/addInvoice']                              = 'invoices/addInvoice';
$route['invoices/updateInvoice']                           = 'invoices/updateInvoice';
$route['invoices/deleteInvoice']                           = 'invoices/deleteInvoice';
$route['invoices/duplicateInvoice']                        = 'invoices/duplicateInvoice';
$route['invoices/updateInvoiceStatus']                     = 'invoices/updateInvoiceStatus';
$route['invoices/getInvoiceDetail']                        = 'invoices/getInvoiceDetail';

// Purchase Orders
$route['purchaseorders']                                       = 'purchaseorders/index';
$route['purchaseorders/create']                                = 'purchaseorders/create';
$route['purchaseorders/(:num)/edit']                           = 'purchaseorders/edit/$1';
$route['purchaseorders/getPurchaseOrdersPageDetails/(:num)']   = 'purchaseorders/getPurchaseOrdersPageDetails/$1';
$route['purchaseorders/getPurchaseOrdersPageDetails']          = 'purchaseorders/getPurchaseOrdersPageDetails';
$route['purchaseorders/addPurchaseOrder']                      = 'purchaseorders/addPurchaseOrder';
$route['purchaseorders/updatePurchaseOrder']                   = 'purchaseorders/updatePurchaseOrder';
$route['purchaseorders/deletePurchaseOrder']                   = 'purchaseorders/deletePurchaseOrder';
$route['purchaseorders/duplicatePurchaseOrder']                = 'purchaseorders/duplicatePurchaseOrder';
$route['purchaseorders/updatePurchaseOrderStatus']             = 'purchaseorders/updatePurchaseOrderStatus';
$route['purchaseorders/getPurchaseOrderDetail']                = 'purchaseorders/getPurchaseOrderDetail';

// Purchases (Purchase Bills)
$route['purchases']                                        = 'purchases/index';
$route['purchases/payments']                               = 'purchases/purchasePayments';
$route['purchases/getPurchasePaymentsPageDetails/(:num)']  = 'purchases/getPurchasePaymentsPageDetails/$1';
$route['purchases/getPurchasePaymentsPageDetails']         = 'purchases/getPurchasePaymentsPageDetails';
$route['purchases/create']                                 = 'purchases/create';
$route['purchases/(:num)/edit']                            = 'purchases/edit/$1';
$route['purchases/getPurchasesPageDetails/(:num)']         = 'purchases/getPurchasesPageDetails/$1';
$route['purchases/getPurchasesPageDetails']                = 'purchases/getPurchasesPageDetails';
$route['purchases/addPurchase']                            = 'purchases/addPurchase';
$route['purchases/updatePurchase']                         = 'purchases/updatePurchase';
$route['purchases/deletePurchase']                         = 'purchases/deletePurchase';
$route['purchases/duplicatePurchase']                      = 'purchases/duplicatePurchase';
$route['purchases/updatePurchaseStatus']                   = 'purchases/updatePurchaseStatus';
$route['purchases/getPurchaseDetail']                      = 'purchases/getPurchaseDetail';

// Sales Returns
$route['salesreturns']                                             = 'salesreturns/index';
$route['salesreturns/create']                                      = 'salesreturns/create';
$route['salesreturns/(:num)/edit']                                 = 'salesreturns/edit/$1';
$route['salesreturns/getSalesReturnsPageDetails']                  = 'salesreturns/getSalesReturnsPageDetails';
$route['salesreturns/addSalesReturn']                              = 'salesreturns/addSalesReturn';
$route['salesreturns/updateSalesReturn']                           = 'salesreturns/updateSalesReturn';
$route['salesreturns/deleteSalesReturn']                           = 'salesreturns/deleteSalesReturn';
$route['salesreturns/duplicateSalesReturn']                        = 'salesreturns/duplicateSalesReturn';
$route['salesreturns/updateSalesReturnStatus']                     = 'salesreturns/updateSalesReturnStatus';
$route['salesreturns/getSalesReturnDetail']                        = 'salesreturns/getSalesReturnDetail';

// Purchase Returns
$route['purchasereturns']                                          = 'purchasereturns/index';
$route['purchasereturns/create']                                   = 'purchasereturns/create';
$route['purchasereturns/(:num)/edit']                              = 'purchasereturns/edit/$1';
$route['purchasereturns/getPurchaseReturnsPageDetails']            = 'purchasereturns/getPurchaseReturnsPageDetails';
$route['purchasereturns/addPurchaseReturn']                        = 'purchasereturns/addPurchaseReturn';
$route['purchasereturns/updatePurchaseReturn']                     = 'purchasereturns/updatePurchaseReturn';
$route['purchasereturns/deletePurchaseReturn']                     = 'purchasereturns/deletePurchaseReturn';
$route['purchasereturns/duplicatePurchaseReturn']                  = 'purchasereturns/duplicatePurchaseReturn';
$route['purchasereturns/updatePurchaseReturnStatus']               = 'purchasereturns/updatePurchaseReturnStatus';
$route['purchasereturns/getPurchaseReturnDetail']                  = 'purchasereturns/getPurchaseReturnDetail';
$route['purchasereturns/recordPayment']                            = 'purchasereturns/recordPayment';
$route['purchasereturns/getPendingPurchases']                      = 'purchasereturns/getPendingPurchases';
$route['purchasereturns/applyDebit']                               = 'purchasereturns/applyDebit';
$route['purchasereturns/getVendorPurchases']                       = 'purchasereturns/getVendorPurchases';
$route['purchasereturns/getPurchaseItems']                         = 'purchasereturns/getPurchaseItems';
$route['purchasereturns/getPRCancelDependencies']                  = 'purchasereturns/getPRCancelDependencies';

// Payments
$route['payments']                                     = 'payments/index';
$route['payments/getPaymentsPageDetails/(:num)']       = 'payments/getPaymentsPageDetails/$1';
$route['payments/getPaymentsPageDetails']              = 'payments/getPaymentsPageDetails';
$route['payments/addPayment']                          = 'payments/addPayment';
$route['payments/getPaymentsByTransaction']            = 'payments/getPaymentsByTransaction';
$route['payments/deletePayment']                       = 'payments/deletePayment';
$route['payments/getPaymentTypes']                     = 'payments/getPaymentTypes';
$route['payments/getBankAccounts']                     = 'payments/getBankAccounts';
$route['payments/saveBankAccount']                     = 'payments/saveBankAccount';
$route['payments/deleteBankAccount']                   = 'payments/deleteBankAccount';
$route['payments/getBankDetails']                      = 'payments/getBankDetails';
$route['payments/setDefaultBank']                      = 'payments/setDefaultBank';
$route['payments/getBanksList']                        = 'payments/getBanksList';
$route['payments/getPaymentDetail']                    = 'payments/getPaymentDetail';

// Customers
$route['customers/(:num)/edit'] = 'customers/edit/$1';
$route['customers/(:num)/clone'] = 'customers/clonecustomer/$1';
$route['customers/modal/(:any)/(:num)'] = 'customers/loadModalForm/$1/$2';
$route['customers/modal/(:any)']        = 'customers/loadModalForm/$1';
$route['customers/getCustomerForModal/(:num)'] = 'customers/getCustomerForModal/$1';
$route['customers/getCustomerBalance']          = 'customers/getCustomerBalance';
$route['customers/updateCustomerBalance']       = 'customers/updateCustomerBalance';
$route['customers/exportCustomers']             = 'customers/exportCustomers';
$route['customers/getCustomerAttachments']      = 'customers/getCustomerAttachments';
$route['customers/saveCustomerAttachments']     = 'customers/saveCustomerAttachments';
$route['customers/deleteCustomerAttachment']    = 'customers/deleteCustomerAttachment';
$route['globally/getCommTemplate'] = 'globally/getCommTemplate';

// Vendors
$route['vendors/(:num)/edit'] = 'vendors/edit/$1';
$route['vendors/(:num)/clone'] = 'vendors/clonevendor/$1';
$route['vendors/modal/(:any)/(:num)'] = 'vendors/loadModalForm/$1/$2';
$route['vendors/modal/(:any)']        = 'vendors/loadModalForm/$1';
$route['vendors/saveVendorOpeningBalance']      = 'vendors/saveVendorOpeningBalance';
$route['vendors/getVendorOpeningBalance']       = 'vendors/getVendorOpeningBalance';
$route['vendors/updateVendorBalance']           = 'vendors/updateVendorBalance';
$route['vendors/exportVendors']                 = 'vendors/exportVendors';
$route['vendors/getVendorAttachments']          = 'vendors/getVendorAttachments';
$route['vendors/deleteVendorAttachment']        = 'vendors/deleteVendorAttachment';

// Products
$route['products/(:num)/edit'] = 'products/edit/$1';
$route['products/(:num)/clone'] = 'products/clone/$1';
$route['products/getAttachments']  = 'products/getAttachments';
$route['products/saveAttachments'] = 'products/saveAttachments';
$route['products/deleteAttachment'] = 'products/deleteAttachment';

// Inventory
$route['inventory']                              = 'inventory/index';
$route['inventory/getPageDetails']               = 'inventory/getPageDetails';
$route['inventory/getPageDetails/(:num)']        = 'inventory/getPageDetails/$1';
$route['inventory/stockIn']                      = 'inventory/stockIn';
$route['inventory/stockOut']                     = 'inventory/stockOut';
$route['inventory/getTimeline']                  = 'inventory/getTimeline';
$route['inventory/getStats']                     = 'inventory/getStats';
$route['inventory/timeline']                     = 'inventory/timelinePage';
$route['inventory/timeline/getPageDetails/(:num)'] = 'inventory/getTimelinePageDetails/$1';
$route['inventory/searchProducts']               = 'inventory/searchProducts';
$route['inventory/export']                       = 'inventory/export';
$route['inventory/exportTimeline']               = 'inventory/exportTimeline';
$route['inventory/updateAdj']                    = 'inventory/updateAdj';
$route['inventory/deleteAdj']                    = 'inventory/deleteAdj';
$route['inventory/updateLedgerRemarks']          = 'inventory/updateLedgerRemarks';

// Machine Rental
$route['rental']                                     = 'rental/index';
$route['rental/getPageDetails/(:num)']               = 'rental/getPageDetails/$1';
$route['rental/getPageDetails']                      = 'rental/getPageDetails';
$route['rental/createRental']                        = 'rental/createRental';
$route['rental/getRentalDetail']                     = 'rental/getRentalDetail';
$route['rental/processReturn']                       = 'rental/processReturn';
$route['rental/recordPayment']                       = 'rental/recordPayment';
$route['rental/cancelRental']                        = 'rental/cancelRental';
$route['rental/searchRentableProducts']              = 'rental/searchRentableProducts';

// Reports Hub
$route['reports'] = 'reports/index';

// Barcode & QR Code Config
$route['settings/barcodeconfig'] = 'barcodeconfig/index';

// ── HRMS — Employees (merged into Users — kept as redirects for backwards compat)
$route['employees']                               = 'users/index';
$route['employees/getEmployeeList']               = 'users/getEmployeeList';

// ── HRMS — Departments ────────────────────────────────────────────────────────
$route['departments']                             = 'departments/index';
$route['departments/getPageDetails/(:num)']       = 'departments/getPageDetails/$1';
$route['departments/save']                        = 'departments/save';
$route['departments/delete']                      = 'departments/delete';
$route['departments/getList']                     = 'departments/getList';

// ── HRMS — Designations ───────────────────────────────────────────────────────
$route['designations']                            = 'designations/index';
$route['designations/getPageDetails/(:num)']      = 'designations/getPageDetails/$1';
$route['designations/save']                       = 'designations/save';
$route['designations/delete']                     = 'designations/delete';
$route['designations/getList']                    = 'designations/getList';

// ── HRMS — Holidays ───────────────────────────────────────────────────────────
$route['holidays']                                = 'holidays/index';
$route['holidays/getPageDetails/(:num)']          = 'holidays/getPageDetails/$1';
$route['holidays/save']                           = 'holidays/save';
$route['holidays/delete']                         = 'holidays/delete';

// ── HRMS — Attendance ─────────────────────────────────────────────────────────
$route['attendance']                              = 'attendance/index';
$route['attendance/getPageDetails/(:num)']        = 'attendance/getPageDetails/$1';
$route['attendance/save']                         = 'attendance/save';
$route['attendance/saveBulk']                     = 'attendance/saveBulk';
$route['attendance/delete']                       = 'attendance/delete';
$route['attendance/monthly']                      = 'attendance/monthly';
$route['attendance/getMonthlyData']               = 'attendance/getMonthlyData';
$route['attendance/getDashboardStats']            = 'attendance/getDashboardStats';

// ── HRMS — Salary Advances ────────────────────────────────────────────────────
$route['salaryadvances']                          = 'salaryadvances/index';
$route['salaryadvances/getPageDetails/(:num)']    = 'salaryadvances/getPageDetails/$1';
$route['salaryadvances/save']                     = 'salaryadvances/save';
$route['salaryadvances/approve']                  = 'salaryadvances/approve';
$route['salaryadvances/reject']                   = 'salaryadvances/reject';
$route['salaryadvances/delete']                   = 'salaryadvances/delete';

// ── HRMS — Payroll ────────────────────────────────────────────────────────────
$route['payroll']                                 = 'payroll/index';
$route['payroll/getPageDetails/(:num)']           = 'payroll/getPageDetails/$1';
$route['payroll/process']                         = 'payroll/process';
$route['payroll/getPayrollEmployees']             = 'payroll/getPayrollEmployees';
$route['payroll/savePayroll']                     = 'payroll/savePayroll';
$route['payroll/updateStatus']                    = 'payroll/updateStatus';
$route['payroll/delete']                          = 'payroll/delete';
$route['payroll/view/(:num)']                     = 'payroll/viewPayroll/$1';

// ── HRMS — Payslips ───────────────────────────────────────────────────────────
$route['payslips']                                = 'payslips/index';
$route['payslips/getPageDetails/(:num)']          = 'payslips/getPageDetails/$1';
$route['payslips/view/(:num)']                    = 'payslips/viewPayslip/$1';
$route['payslips/print/(:num)']                   = 'payslips/printPayslip/$1';

// Print Themes
$route['settings/printthemes']                    = 'printthemes/index';
$route['settings/printthemes/save']               = 'printthemes/save';
$route['settings/printthemes/delete']             = 'printthemes/delete';
$route['settings/printthemes/getThemeData']       = 'printthemes/getThemeData';
$route['settings/printthemes/getThemeList']       = 'printthemes/getThemeList';
$route['settings/printthemes/saveTheme']          = 'printthemes/saveTheme';
$route['settings/printthemes/deleteTheme']        = 'printthemes/deleteTheme';
$route['settings/printthemes/getTemplateList']    = 'printthemes/getTemplateList';
$route['settings/printthemes/saveTemplate']       = 'printthemes/saveTemplate';
$route['settings/printthemes/deleteTemplate']     = 'printthemes/deleteTemplate';

// ── User Preferences ─────────────────────────────────────────────────────────
$route['userpreferences/save']   = 'userpreferences/save';
$route['userpreferences/getAll'] = 'userpreferences/getAll';
$route['settings/printthemes/getTemplateData']    = 'printthemes/getTemplateData';