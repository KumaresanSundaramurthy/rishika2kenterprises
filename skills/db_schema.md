-- `Transaction`.TransactionsTbl definition

CREATE TABLE "TransactionsTbl" (
  "TransUID" bigint NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "DispatchFromUID" int unsigned DEFAULT NULL,
  "ModuleUID" mediumint unsigned NOT NULL,
  "PrefixUID" int DEFAULT NULL COMMENT 'FK TransactionPrefixTbl PrefixUID',
  "UniqueNumber" varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "TransType" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "TransNumber" varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "TransDate" date NOT NULL,
  "TransYear" int NOT NULL DEFAULT '0' COMMENT 'Calendar year of TransDate — used in unique key instead of YEAR(TransDate)',
  "DocStatus" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Draft' COMMENT 'Quotation lifecycle status',
  "QuotationType" varchar(50) DEFAULT NULL,
  "DispatchFrom" varchar(100) DEFAULT NULL,
  "FinancialYear" smallint GENERATED ALWAYS AS (year(`TransDate`)) STORED NOT NULL,
  "PartyUID" int unsigned NOT NULL,
  "PartyType" enum('C','S','E') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'C',
  "TotalItems" tinyint unsigned NOT NULL DEFAULT '0',
  "TotalQuantity" decimal(15,2) NOT NULL DEFAULT '0.00',
  "SubTotal" decimal(15,2) NOT NULL DEFAULT '0.00',
  "GrossAmount" decimal(15,2) DEFAULT '0.00',
  "TaxableAmount" decimal(15,2) DEFAULT '0.00',
  "CgstAmount" decimal(15,3) DEFAULT '0.000',
  "SgstAmount" decimal(15,3) DEFAULT '0.000',
  "IgstAmount" decimal(15,3) DEFAULT '0.000',
  "DiscountAmount" decimal(15,2) DEFAULT '0.00',
  "AdditionalCharges" decimal(15,2) NOT NULL DEFAULT '0.00',
  "TaxAmount" decimal(15,2) NOT NULL DEFAULT '0.00',
  "RoundOff" decimal(8,2) NOT NULL DEFAULT '0.00',
  "NetAmount" decimal(15,2) DEFAULT '0.00',
  "IsFullyPaid" bit(1) NOT NULL DEFAULT b'0',
  "PaidAmount" decimal(15,2) DEFAULT '0.00' COMMENT 'Total amount received via payments',
  "BalanceAmount" decimal(15,2) DEFAULT '0.00' COMMENT 'NetAmount - PaidAmount; 0 when fully paid',
  "GlobalDiscPercent" decimal(7,2) NOT NULL DEFAULT '0.00',
  "ExtraDiscApplied" bit(1) NOT NULL DEFAULT b'0',
  "ExtraDiscType" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "ExtraDiscAmount" decimal(15,2) DEFAULT '0.00',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("TransUID","FinancialYear"),
  UNIQUE KEY "uidx_unique_number_year" ("FinancialYear","UniqueNumber"),
  KEY "TransactionsTbl_PartyQuery" ("ModuleUID","PartyUID","TransDate"),
  KEY "TransactionsTbl_TransNumber" ("TransNumber"),
  KEY "TransactionsTbl_UniqueNumber" ("UniqueNumber"),
  KEY "TransactionsTbl_DateType" ("TransDate","TransType"),
  KEY "TransactionsTbl_Party_Date_Amount" ("PartyUID","TransDate","NetAmount"),
  KEY "idx_transactions_active" ("IsDeleted","IsActive","TransUID"),
  KEY "idx_transactions_dates" ("TransDate"),
  KEY "idx_module_org" ("ModuleUID","OrgUID"),
  KEY "idx_module_status" ("ModuleUID","DocStatus")
)
/*!50100 PARTITION BY RANGE ("FinancialYear")
(PARTITION p2023 VALUES LESS THAN (2024),
 PARTITION p2024 VALUES LESS THAN (2025),
 PARTITION p2025 VALUES LESS THAN (2026),
 PARTITION p2026 VALUES LESS THAN (2027),
 PARTITION p_future VALUES LESS THAN MAXVALUE) */;


 -- `Transaction`.TransDetailTbl definition

CREATE TABLE "TransDetailTbl" (
  "TransUID" bigint NOT NULL,
  "ValidityDays" tinyint unsigned DEFAULT NULL,
  "ValidityDate" date DEFAULT NULL,
  "Reference" varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "FinancialYear" smallint NOT NULL,
  "Notes" text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  "TermsConditions" text,
  "ShippingAddress" json DEFAULT NULL,
  "AdditionalCharges" json DEFAULT NULL,
  "PaymentTerms" text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  "DeliveryDate" date DEFAULT NULL,
  "PlaceOfSupply" varchar(100) DEFAULT NULL,
  "IsForeignCustomer" tinyint(1) DEFAULT NULL COMMENT '1=foreign,0=Indian,NULL=vendor tx',
  "IsInterState" tinyint(1) DEFAULT NULL COMMENT '1=IGST,0=CGST+SGST,NULL=no tax',
  "IRN" varchar(100) DEFAULT NULL,
  "IRNQRCode" text,
  "AckNo" varchar(100) DEFAULT NULL,
  "AckDate" datetime DEFAULT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("FinancialYear","TransUID"),
  KEY "TransDetailTbl_Reference_IDX" ("Reference"),
  KEY "TransDetailTbl_IRN_IDX" ("IRN")
)
/*!50100 PARTITION BY RANGE ("FinancialYear")
(PARTITION p2023 VALUES LESS THAN (2024),
 PARTITION p2024 VALUES LESS THAN (2025),
 PARTITION p2025 VALUES LESS THAN (2026),
 PARTITION p2026 VALUES LESS THAN (2027),
 PARTITION p_future VALUES LESS THAN MAXVALUE) */;

 -- `Transaction`.TransHistoryTbl definition

CREATE TABLE "TransHistoryTbl" (
  "TransHistoryUID" bigint NOT NULL AUTO_INCREMENT,
  "FinancialYear" smallint NOT NULL,
  "TransUID" bigint NOT NULL,
  "ActionTime" datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  "ActionType" varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "OldStatus" tinyint unsigned DEFAULT NULL,
  "NewStatus" tinyint unsigned DEFAULT NULL,
  "UserUID" mediumint unsigned NOT NULL,
  "IPAddress" varchar(50) DEFAULT NULL,
  "RelatedTransUID" int DEFAULT NULL COMMENT 'Target TransUID when ActionType is conversion',
  PRIMARY KEY ("TransHistoryUID"),
  KEY "idx_trans_history" ("FinancialYear","TransUID","ActionTime" DESC),
  KEY "idx_user_actions" ("UserUID","ActionTime" DESC),
  KEY "idx_status_changes" ("OldStatus","NewStatus","ActionTime")
);

-- `Transaction`.TransProdTaxesTbl definition

CREATE TABLE "TransProdTaxesTbl" (
  "TransProdTaxUID" bigint NOT NULL AUTO_INCREMENT,
  "FinancialYear" smallint NOT NULL,
  "TransUID" bigint NOT NULL,
  "TransProdUID" bigint NOT NULL,
  "TaxType" enum('CGST','SGST','IGST','CESS','KKC','TDS','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "TaxName" varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "TaxRate" decimal(5,2) DEFAULT '0.00',
  "TaxableAmount" decimal(15,2) DEFAULT NULL,
  "TaxAmount" decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY ("TransProdTaxUID"),
  KEY "TransProdTaxesTbl_TransProductsTbl_FK" ("TransProdUID"),
  CONSTRAINT "TransProdTaxesTbl_TransProductsTbl_FK" FOREIGN KEY ("TransProdUID") REFERENCES "TransProductsTbl" ("TransProdUID") ON DELETE CASCADE
);

-- `Transaction`.TransProductsTbl definition

CREATE TABLE "TransProductsTbl" (
  "TransProdUID" bigint NOT NULL AUTO_INCREMENT,
  "FinancialYear" smallint NOT NULL,
  "TransUID" bigint NOT NULL,
  "ItemSequence" tinyint unsigned NOT NULL,
  "ProductUID" int unsigned DEFAULT NULL,
  "ProductName" varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "PartNumber" varchar(50) DEFAULT NULL,
  "CategoryUID" int unsigned DEFAULT NULL,
  "StorageUID" int unsigned DEFAULT NULL,
  "Quantity" decimal(15,2) NOT NULL DEFAULT '1.00',
  "PrimaryUnitName" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "TaxDetailsUID" tinyint unsigned NOT NULL,
  "TaxPercentage" tinyint unsigned NOT NULL,
  "CGST" tinyint unsigned NOT NULL,
  "SGST" tinyint unsigned NOT NULL,
  "IGST" tinyint unsigned NOT NULL,
  "DiscountTypeUID" tinyint unsigned DEFAULT NULL,
  "Discount" decimal(15,2) DEFAULT NULL,
  "UnitPrice" decimal(15,2) NOT NULL DEFAULT '0.00',
  "SellingPrice" decimal(15,2) NOT NULL,
  "CgstAmount" decimal(15,2) DEFAULT '0.00',
  "SgstAmount" decimal(15,2) DEFAULT '0.00',
  "IgstAmount" decimal(15,2) DEFAULT '0.00',
  "TaxAmount" decimal(15,2) DEFAULT '0.00',
  "DiscountAmount" decimal(15,2) DEFAULT '0.00',
  "NetAmount" decimal(15,2) DEFAULT '0.00',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  "QuantityConverted" int NOT NULL DEFAULT '0' COMMENT 'Total qty moved to downstream docs so far',
  "QuantityRemaining" int GENERATED ALWAYS AS ((`Quantity` - `QuantityConverted`)) VIRTUAL COMMENT 'Unfulfilled qty (computed)',
  PRIMARY KEY ("TransProdUID"),
  UNIQUE KEY "uidx_year_trans_seq" ("FinancialYear","TransUID","ItemSequence"),
  KEY "TransProductsTbl_ProductUID_IDX" ("ProductUID","FinancialYear")
);

-- `Transaction`.TransactionPrefixTbl definition

CREATE TABLE "TransactionPrefixTbl" (
  "PrefixUID" int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "ModuleUID" int unsigned NOT NULL,
  "Name" varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" int unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" int unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("PrefixUID"),
  UNIQUE KEY "TransactionPrefixTbl_UNIQUE" ("Name"),
  KEY "TransactionPrefixTbl_ModuleUID_IDX" ("ModuleUID"),
  KEY "TransactionPrefixTbl_IsActive_IDX" ("IsActive"),
  KEY "TransactionPrefixTbl_IsDeleted_IDX" ("IsDeleted"),
  KEY "TransactionPrefixTbl_UpdatedBy_IDX" ("UpdatedBy"),
  KEY "TransactionPrefixTbl_CreatedBy_IDX" ("CreatedBy")
);

-- Accounting.ChartOfAccounts definition

CREATE TABLE "ChartOfAccounts" (
  "LedgerUID" int NOT NULL AUTO_INCREMENT,
  "LedgerCode" varchar(50) NOT NULL,
  "LedgerName" varchar(255) NOT NULL,
  "LedgerType" enum('Asset','Liability','Income','Expense','Customer','Vendor','Supplier','Employee','Bank','Cash') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "ParentLedgerUID" int DEFAULT NULL,
  "OpeningBalance" decimal(15,2) DEFAULT '0.00',
  "OpeningBalanceType" enum('Debit','Credit') DEFAULT 'Debit',
  "CurrentBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "CurrentBalanceType" enum('Debit','Credit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Debit',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" int unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" int unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("LedgerUID"),
  UNIQUE KEY "LedgerCode" ("LedgerCode"),
  KEY "idx_ledger_type" ("LedgerType"),
  KEY "idx_parent" ("ParentLedgerUID"),
  CONSTRAINT "ChartOfAccounts_ibfk_1" FOREIGN KEY ("ParentLedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID")
);

-- Accounting.EntityLedgerMap definition

CREATE TABLE "EntityLedgerMap" (
  "MapID" int NOT NULL AUTO_INCREMENT,
  "LedgerUID" int NOT NULL,
  "CustomerUID" int unsigned DEFAULT NULL,
  "VendorUID" int unsigned DEFAULT NULL,
  "UserUID" int unsigned DEFAULT NULL,
  "BankUID" int unsigned DEFAULT NULL,
  "EntityType" enum('Customer','Vendor','Employee','Bank') NOT NULL,
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "CreatedBy" int unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" int unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("MapID"),
  UNIQUE KEY "uk_ledger_entity" ("LedgerUID","EntityType","CustomerUID","VendorUID","UserUID","BankUID"),
  KEY "idx_customer" ("CustomerUID"),
  KEY "idx_vendor" ("VendorUID"),
  KEY "idx_employee" ("UserUID"),
  KEY "idx_entity_type" ("EntityType"),
  KEY "idx_ledger" ("LedgerUID"),
  CONSTRAINT "EntityLedgerMap_ibfk_1" FOREIGN KEY ("LedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID") ON DELETE CASCADE,
  CONSTRAINT "EntityLedgerMap_ibfk_2" FOREIGN KEY ("CustomerUID") REFERENCES "Customers"."CustomerTbl" ("CustomerUID") ON DELETE CASCADE,
  CONSTRAINT "EntityLedgerMap_ibfk_3" FOREIGN KEY ("VendorUID") REFERENCES "Vendors"."VendorTbl" ("VendorUID") ON DELETE CASCADE,
  CONSTRAINT "EntityLedgerMap_ibfk_4" FOREIGN KEY ("UserUID") REFERENCES "Users"."UserTbl" ("UserUID") ON DELETE CASCADE,
  CONSTRAINT "chk_single_entity" CHECK ((((`CustomerUID` is not null) and (`VendorUID` is null) and (`UserUID` is null) and (`BankUID` is null)) or ((`CustomerUID` is null) and (`VendorUID` is not null) and (`UserUID` is null) and (`BankUID` is null)) or ((`CustomerUID` is null) and (`VendorUID` is null) and (`UserUID` is not null) and (`BankUID` is null)) or ((`CustomerUID` is null) and (`VendorUID` is null) and (`UserUID` is null) and (`BankUID` is not null))))
);

-- Accounting.GeneralJournal definition

CREATE TABLE "GeneralJournal" (
  "JournalUID" bigint NOT NULL AUTO_INCREMENT,
  "JournalNo" varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "JournalDate" date NOT NULL,
  "FinancialYear" smallint NOT NULL,
  "ReferenceType" varchar(50) DEFAULT NULL,
  "ReferenceID" bigint DEFAULT NULL,
  "ReferenceNo" varchar(100) DEFAULT NULL,
  "Narration" text,
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "CreatedBy" int NOT NULL,
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("JournalUID"),
  UNIQUE KEY "VoucherNo" ("JournalNo"),
  KEY "idx_voucher_date" ("JournalDate","FinancialYear"),
  KEY "idx_reference" ("ReferenceType","ReferenceID")
);

-- Accounting.JournalEntries definition

CREATE TABLE "JournalEntries" (
  "EntryUID" bigint NOT NULL AUTO_INCREMENT,
  "JournalUID" bigint NOT NULL,
  "LedgerUID" int NOT NULL,
  "TransactionType" enum('Debit','Credit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "Amount" decimal(15,2) DEFAULT '0.00',
  "Particulars" text,
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "CreatedBy" int unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" int unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("EntryUID"),
  KEY "idx_journal" ("JournalUID"),
  KEY "idx_ledger" ("LedgerUID"),
  CONSTRAINT "JournalEntries_ibfk_1" FOREIGN KEY ("JournalUID") REFERENCES "GeneralJournal" ("JournalUID") ON DELETE CASCADE,
  CONSTRAINT "JournalEntries_ibfk_2" FOREIGN KEY ("LedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID"),
  CONSTRAINT "JournalEntries_CHECK" CHECK ((`Amount` > 0))
);

-- Accounting.LedgerAuditTrail definition

CREATE TABLE "LedgerAuditTrail" (
  "AuditID" int NOT NULL AUTO_INCREMENT,
  "LedgerUID" int NOT NULL,
  "CustomerUID" int unsigned DEFAULT NULL,
  "VendorUID" int unsigned DEFAULT NULL,
  "UserUID" int unsigned DEFAULT NULL,
  "BankUID" int unsigned DEFAULT NULL,
  "EntityType" enum('Customer','Vendor','Employee','Bank','System') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "ChangeType" enum('CREATE','UPDATE','DELETE','BALANCE_ADJUST') NOT NULL,
  "FieldChanged" varchar(100) DEFAULT NULL,
  "OldValue" text,
  "NewValue" text,
  "ChangeDetails" json DEFAULT NULL,
  "IPAddress" varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "UserAgent" varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "CreatedBy" int unsigned DEFAULT '1',
  "UpdatedBy" int unsigned DEFAULT NULL,
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("AuditID"),
  KEY "LedgerAuditTrail_ChartOfAccounts_FK" ("LedgerUID"),
  KEY "idx_vendor" ("VendorUID"),
  KEY "idx_user" ("UserUID"),
  KEY "idx_entity_type" ("EntityType"),
  KEY "LedgerAuditTrail_CustomerTbl_FK" ("CustomerUID"),
  CONSTRAINT "LedgerAuditTrail_ChartOfAccounts_FK" FOREIGN KEY ("LedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID"),
  CONSTRAINT "LedgerAuditTrail_CustomerTbl_FK" FOREIGN KEY ("CustomerUID") REFERENCES "Customers"."CustomerTbl" ("CustomerUID") ON DELETE SET NULL,
  CONSTRAINT "LedgerAuditTrail_EmployeeTbl_FK" FOREIGN KEY ("UserUID") REFERENCES "Users"."UserTbl" ("UserUID") ON DELETE SET NULL,
  CONSTRAINT "LedgerAuditTrail_VendorTbl_FK" FOREIGN KEY ("VendorUID") REFERENCES "Vendors"."VendorTbl" ("VendorUID") ON DELETE SET NULL
);

-- Accounting.LedgerBalances definition

CREATE TABLE "LedgerBalances" (
  "BalanceUID" bigint NOT NULL AUTO_INCREMENT,
  "LedgerUID" int NOT NULL,
  "FinancialYear" smallint NOT NULL,
  "TransactionDate" date NOT NULL,
  "JournalUID" bigint NOT NULL,
  "DebitAmount" decimal(15,2) DEFAULT '0.00',
  "CreditAmount" decimal(15,2) DEFAULT '0.00',
  "RunningBalance" decimal(15,2) DEFAULT '0.00',
  "BalanceType" enum('Debit','Credit') DEFAULT 'Debit',
  PRIMARY KEY ("BalanceUID"),
  UNIQUE KEY "LedgerUID" ("LedgerUID","JournalUID"),
  KEY "idx_ledger_year" ("LedgerUID","FinancialYear","TransactionDate"),
  KEY "JournalUID" ("JournalUID"),
  CONSTRAINT "LedgerBalances_ibfk_1" FOREIGN KEY ("LedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID"),
  CONSTRAINT "LedgerBalances_ibfk_2" FOREIGN KEY ("JournalUID") REFERENCES "GeneralJournal" ("JournalUID")
);

-- Accounting.LedgerYearOpening definition

CREATE TABLE "LedgerYearOpening" (
  "LedgerUID" int NOT NULL,
  "FinancialYear" smallint NOT NULL,
  "OpeningBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "OpeningBalanceType" enum('Debit','Credit') DEFAULT 'Debit',
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("LedgerUID","FinancialYear"),
  CONSTRAINT "fk_ledger" FOREIGN KEY ("LedgerUID") REFERENCES "ChartOfAccounts" ("LedgerUID")
);

-- `Transaction`.PaymentsTbl definition

CREATE TABLE "PaymentsTbl" (
  "PaymentUID"        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  "OrgUID"            INT UNSIGNED    NOT NULL,
  "PaymentDate"       DATE            NULL     COMMENT 'Date of payment',
  "PrefixUID"         INT UNSIGNED    NULL     COMMENT 'FK TransactionPrefixTbl.PrefixUID (ModuleUID=110)',
  "PaymentNumber"     INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Sequential number per prefix+org+year',
  "UniqueNumber"      VARCHAR(30)     NULL     COMMENT 'Formatted payment ref e.g. PAY-001',
  "TransYear"         SMALLINT        NOT NULL DEFAULT 0 COMMENT 'Calendar year of PaymentDate',
  "TransUID"          BIGINT UNSIGNED NOT NULL,
  "ModuleUID"         INT UNSIGNED    NOT NULL,
  "PartyType"         CHAR(1)         NOT NULL COMMENT 'C=Customer V=Vendor',
  "PartyUID"          INT UNSIGNED    NOT NULL,
  "PaymentTypeUID"    INT UNSIGNED    NOT NULL,
  "Amount"            DECIMAL(15,4)   NOT NULL DEFAULT 0,
  "BankAccountUID"    INT UNSIGNED    NULL     COMMENT 'NULL for cash payments',
  "ReferenceNo"       VARCHAR(100)    NULL     COMMENT 'Cheque no, UTR, UPI ref',
  "Notes"             VARCHAR(255)    NULL,
  "PaymentSource"     ENUM('Create','Record') NOT NULL DEFAULT 'Record' COMMENT 'Create=at invoice creation, Record=via Record Payment modal',
  "IsFullyPaid"       TINYINT(1)      NOT NULL DEFAULT 0,
  "ExcessAmount"      DECIMAL(15,4)   NOT NULL DEFAULT 0 COMMENT 'Amount paid beyond bill total',
  "AppliedToTransUID" INT UNSIGNED    NULL     COMMENT 'Excess applied to another transaction',
  "IsActive"          TINYINT(1)      NOT NULL DEFAULT 1,
  "IsDeleted"         TINYINT(1)      NOT NULL DEFAULT 0,
  "CreatedBy"         INT UNSIGNED    NULL,
  "UpdatedBy"         INT UNSIGNED    NULL,
  "CreatedOn"         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn"         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("PaymentUID"),
  UNIQUE KEY "uidx_pay_unique_number"  ("OrgUID","PrefixUID","TransYear","UniqueNumber"),
  KEY "idx_payments_trans"             ("TransUID","IsDeleted"),
  KEY "idx_payments_org"              ("OrgUID","IsDeleted","IsActive"),
  KEY "idx_payments_party"            ("OrgUID","PartyType","PartyUID"),
  KEY "idx_payments_year"             ("OrgUID","TransYear")
);


-- Products.ProductBOMTbl definition

CREATE TABLE "ProductBOMTbl" (
  "ComponentUID" int NOT NULL AUTO_INCREMENT,
  "OrgUID" int NOT NULL,
  "ParentProductUID" int NOT NULL,
  "ChildProductUID" int NOT NULL,
  "Quantity" decimal(18,4) NOT NULL DEFAULT '1.0000',
  "IsActive" tinyint(1) NOT NULL DEFAULT '1',
  "IsDeleted" tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy" int NOT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedBy" int DEFAULT NULL,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("ComponentUID"),
  KEY "idx_bom_parent" ("ParentProductUID"),
  KEY "idx_bom_child" ("ChildProductUID"),
  KEY "idx_bom_active" ("IsActive","IsDeleted")
);

-- Products.ProductBatchTbl definition

CREATE TABLE "ProductBatchTbl" (
  "BatchUID" int NOT NULL AUTO_INCREMENT,
  "OrgUID" int NOT NULL,
  "ProductUID" int NOT NULL,
  "VendorUID" int NOT NULL,
  "PurchasePrice" decimal(18,4) NOT NULL DEFAULT '0.0000',
  "PurchaseDate" date NOT NULL,
  "Quantity" decimal(18,4) NOT NULL DEFAULT '0.0000',
  "CreatedBy" int NOT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("BatchUID"),
  KEY "idx_batch_product" ("ProductUID"),
  KEY "idx_batch_vendor" ("VendorUID"),
  KEY "idx_batch_date" ("PurchaseDate")
);

-- Products.ProductRateTbl definition

CREATE TABLE "ProductRateTbl" (
  "ProductRateUID" int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "ProductUID" int unsigned NOT NULL,
  "CustomerTypeUID" tinyint unsigned NOT NULL,
  "SellingPrice" decimal(10,3) NOT NULL DEFAULT '0.000',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "CreatedBy" int unsigned NOT NULL,
  "UpdatedBy" int unsigned NOT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("ProductRateUID"),
  KEY "idx_prate_product" ("ProductUID"),
  KEY "idx_prate_ctype" ("CustomerTypeUID")
);

-- Products.ProductSerialTbl definition

CREATE TABLE "ProductSerialTbl" (
  "SerialUID" int NOT NULL AUTO_INCREMENT,
  "OrgUID" int NOT NULL,
  "ProductUID" int NOT NULL,
  "SerialNo" varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  "VendorUID" int DEFAULT NULL,
  "PurchaseDate" date DEFAULT NULL,
  "Status" enum('Available','Sold','Defective') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Available',
  "CustomerUID" int DEFAULT NULL,
  "SoldDate" date DEFAULT NULL,
  "CreatedBy" int NOT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedBy" int DEFAULT NULL,
  "UpdatedOn" datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("SerialUID"),
  UNIQUE KEY "uq_serial_product" ("ProductUID","SerialNo"),
  KEY "idx_serial_product" ("ProductUID"),
  KEY "idx_serial_status" ("Status")
);

-- Products.ProductTbl definition

CREATE TABLE "ProductTbl" (
  "ProductUID" int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "ItemName" varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "ProductType" enum('Product','Service') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Product',
  "UnitPrice" decimal(10,2) NOT NULL,
  "MRP" decimal(10,2) NOT NULL DEFAULT '0.00',
  "SellingPrice" decimal(10,2) NOT NULL,
  "SellingProductTaxUID" tinyint unsigned NOT NULL DEFAULT '1',
  "TaxDetailsUID" tinyint unsigned NOT NULL,
  "TaxPercentage" tinyint unsigned NOT NULL,
  "CGST" tinyint unsigned NOT NULL,
  "SGST" tinyint unsigned NOT NULL,
  "IGST" tinyint unsigned NOT NULL,
  "PrimaryUnitUID" int DEFAULT NULL,
  "CategoryUID" int DEFAULT NULL,
  "StorageUID" int unsigned DEFAULT NULL,
  "HSNSACCode" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "PurchasePrice" decimal(10,2) DEFAULT '0.00',
  "PurchasePriceProductTaxUID" tinyint unsigned NOT NULL DEFAULT '1',
  "PartNumber" varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "SKU" varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "Description" text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  "Image" varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "OpeningQuantity" decimal(10,2) NOT NULL DEFAULT '0.00',
  "OpeningPurchasePrice" decimal(10,2) NOT NULL DEFAULT '0.00',
  "OpeningStockValue" int unsigned NOT NULL DEFAULT '0',
  "Discount" decimal(10,2) NOT NULL DEFAULT '0.00',
  "DiscountTypeUID" tinyint unsigned NOT NULL DEFAULT '1',
  "LowStockAlertAt" int unsigned NOT NULL DEFAULT '0',
  "NotForSale" char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'No',
  "IsSizeApplicable" bit(1) NOT NULL DEFAULT b'0',
  "IsComboItem" tinyint(1) NOT NULL DEFAULT '0',
  "IsComposite" tinyint(1) NOT NULL DEFAULT '0',
  "IsBrandApplicable" tinyint(1) NOT NULL DEFAULT '0',
  "IsSerialTracked" tinyint(1) NOT NULL DEFAULT '0',
  "AvailableQuantity" decimal(15,2) NOT NULL DEFAULT '0.00',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" int unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" int unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("ProductUID"),
  UNIQUE KEY "ProductTbl_UNIQUE" ("ItemName"),
  UNIQUE KEY "ProductTbl_SKU_UNIQUE" ("SKU"),
  KEY "ProductTbl_CategoryUID_IDX" ("CategoryUID"),
  KEY "ProductTbl_SellingProductTaxUID_IDX" ("SellingProductTaxUID"),
  KEY "ProductTbl_TaxDetailsUID_IDX" ("TaxDetailsUID"),
  KEY "ProductTbl_PrimaryUnitUID_IDX" ("PrimaryUnitUID"),
  KEY "ProductTbl_StorageUID_IDX" ("StorageUID"),
  KEY "ProductTbl_PurchasePriceProductTaxUID_IDX" ("PurchasePriceProductTaxUID"),
  KEY "ProductTbl_PartNumber_IDX" ("PartNumber"),
  KEY "ProductTbl_DiscountTypeUID_IDX" ("DiscountTypeUID")
);

-- Products.StockLedgerTbl definition

CREATE TABLE "StockLedgerTbl" (
  "LedgerUID" bigint unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "ProductUID" int unsigned NOT NULL,
  "TransUID" int unsigned NOT NULL,
  "ModuleUID" int unsigned NOT NULL,
  "MovementType" enum('IN','OUT') NOT NULL COMMENT 'IN=stock increase, OUT=stock decrease',
  "Quantity" decimal(15,4) NOT NULL DEFAULT '0.0000',
  "UnitCost" decimal(15,4) NOT NULL DEFAULT '0.0000',
  "IsDeleted" tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy" int unsigned NOT NULL,
  "UpdatedBy" int unsigned NOT NULL,
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("LedgerUID"),
  KEY "idx_stock_product" ("OrgUID","ProductUID","IsDeleted"),
  KEY "idx_stock_trans" ("TransUID","IsDeleted")
);

-- Run this once against your MySQL database to add the Description column to TransProductsTbl
ALTER TABLE `Transaction`.`TransProductsTbl`
    ADD COLUMN IF NOT EXISTS `Description` TEXT NULL DEFAULT NULL AFTER `ProductName`;

-- `Global`.CitiesTbl definition

CREATE TABLE "CitiesTbl" (
  "id" mediumint unsigned NOT NULL AUTO_INCREMENT,
  "name" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "state_id" mediumint unsigned NOT NULL,
  "state_code" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "country_id" mediumint unsigned NOT NULL,
  "country_code" char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "type" varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "level" int DEFAULT NULL,
  "parent_id" int unsigned DEFAULT NULL,
  "latitude" decimal(10,8) NOT NULL,
  "longitude" decimal(11,8) NOT NULL,
  "native" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "population" bigint unsigned DEFAULT NULL,
  "timezone" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IANA timezone identifier (e.g., America/New_York)',
  "translations" text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  "created_at" timestamp NOT NULL DEFAULT '2014-01-01 12:01:01',
  "updated_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  "flag" tinyint(1) NOT NULL DEFAULT '1',
  "wikiDataId" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  PRIMARY KEY ("id"),
  KEY "cities_test_ibfk_1" ("state_id"),
  KEY "cities_test_ibfk_2" ("country_id"),
  CONSTRAINT "cities_ibfk_1" FOREIGN KEY ("state_id") REFERENCES "StatesTbl" ("id"),
  CONSTRAINT "cities_ibfk_2" FOREIGN KEY ("country_id") REFERENCES "CountriesTbl" ("id")
);

-- `Global`.StatesTbl definition

CREATE TABLE "StatesTbl" (
  "id" mediumint unsigned NOT NULL AUTO_INCREMENT,
  "name" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "country_id" mediumint unsigned NOT NULL,
  "country_code" char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "fips_code" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "iso2" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "iso3166_2" varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "type" varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "level" int DEFAULT NULL,
  "parent_id" int unsigned DEFAULT NULL,
  "native" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "latitude" decimal(10,8) DEFAULT NULL,
  "longitude" decimal(11,8) DEFAULT NULL,
  "timezone" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IANA timezone identifier (e.g., America/New_York)',
  "translations" text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  "created_at" timestamp NULL DEFAULT NULL,
  "updated_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  "flag" tinyint(1) NOT NULL DEFAULT '1',
  "wikiDataId" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  "population" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY ("id"),
  KEY "country_region" ("country_id"),
  CONSTRAINT "country_region_final" FOREIGN KEY ("country_id") REFERENCES "countries" ("id")
);

-- `Global`.CountriesTbl definition

CREATE TABLE "CountriesTbl" (
  "id" mediumint unsigned NOT NULL AUTO_INCREMENT,
  "name" varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  "iso3" char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "numeric_code" char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "iso2" char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "phonecode" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "capital" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "currency" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "currency_name" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "currency_symbol" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "tld" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "native" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "population" bigint unsigned DEFAULT NULL,
  "gdp" bigint unsigned DEFAULT NULL,
  "region" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "region_id" mediumint unsigned DEFAULT NULL,
  "subregion" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "subregion_id" mediumint unsigned DEFAULT NULL,
  "nationality" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "area_sq_km" double DEFAULT NULL,
  "postal_code_format" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "postal_code_regex" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "timezones" text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  "translations" text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  "latitude" decimal(10,8) DEFAULT NULL,
  "longitude" decimal(11,8) DEFAULT NULL,
  "emoji" varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "emojiU" varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  "created_at" timestamp NULL DEFAULT NULL,
  "updated_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  "flag" tinyint(1) NOT NULL DEFAULT '1',
  "wikiDataId" varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  PRIMARY KEY ("id"),
  KEY "country_continent" ("region_id"),
  KEY "country_subregion" ("subregion_id"),
  CONSTRAINT "country_continent_final" FOREIGN KEY ("region_id") REFERENCES "RegionsTbl" ("id"),
  CONSTRAINT "country_subregion_final" FOREIGN KEY ("subregion_id") REFERENCES "SubRegionsTbl" ("id")
);

-- Organisation.ThermalPrintConfigTbl definition

CREATE TABLE "ThermalPrintConfigTbl" (
  "ThermalConfigUID" int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "ModuleUID" mediumint unsigned NOT NULL DEFAULT '0' COMMENT '101=Quotation, 102=SalesOrder, 103=Invoice, 104=PurchaseOrder, 105=Purchase, 110=Payment, 111=PaymentOut',
  "TransactionType" varchar(30) NOT NULL DEFAULT 'Quotation' COMMENT 'Quotation | Invoice | SalesOrder | PurchaseOrder | Purchase | SalesReturn | PurchaseReturn | CreditNote | DebitNote',
  "PaperWidth" varchar(10) NOT NULL DEFAULT '80mm' COMMENT '58mm or 80mm',
  "OrgNameFontSize" tinyint unsigned NOT NULL DEFAULT '16' COMMENT 'Font size for org/brand name in header',
  "CompanyNameFontSize" tinyint unsigned NOT NULL DEFAULT '14' COMMENT 'Font size for company name line in header',
  "ProductInfoFontSize" tinyint unsigned NOT NULL DEFAULT '12',
  "ShowCompanyDetails" bit(1) NOT NULL DEFAULT b'1' COMMENT 'Include company details on receipt',
  "ShowGSTIN" bit(1) NOT NULL DEFAULT b'0',
  "ShowMobile" bit(1) NOT NULL DEFAULT b'1',
  "ShowLogo" bit(1) NOT NULL DEFAULT b'1',
  "ShowItemDescription" bit(1) NOT NULL DEFAULT b'0' COMMENT 'Print detailed product descriptions',
  "ShowHSN" bit(1) NOT NULL DEFAULT b'0',
  "ShowTaxableAmount" bit(1) NOT NULL DEFAULT b'1' COMMENT 'Display taxable amount above tax line',
  "ShowTaxBreakdown" bit(1) NOT NULL DEFAULT b'1',
  "ShowCashReceived" bit(1) NOT NULL DEFAULT b'1' COMMENT 'Display amount received from customer',
  "ShowGoogleReviewQR" bit(1) NOT NULL DEFAULT b'0' COMMENT 'Print Google Reviews QR code',
  "ShowPaymentQR" bit(1) NOT NULL DEFAULT b'1' COMMENT 'Print UPI/payment QR code',
  "ShowTerms" bit(1) NOT NULL DEFAULT b'0' COMMENT 'Print terms & conditions on receipt',
  "FooterMessage" varchar(200) DEFAULT 'Thank you for your business!',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "CreatedBy" int unsigned DEFAULT NULL,
  "UpdatedBy" int unsigned DEFAULT NULL,
  "CreatedOn" datetime DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("ThermalConfigUID"),
  UNIQUE KEY "uq_org_thermal_type" ("OrgUID","TransactionType")
);

-- Customers.CustOpeningBalanceTbl definition
-- One row per customer — stores the cumulative running opening balance.
-- FinancialYear removed; use CustYearOpeningBalanceTbl for per-year snapshots.

CREATE TABLE "CustOpeningBalanceTbl" (
  "OpeningBalUID"  int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID"         int unsigned NOT NULL,
  "CustomerUID"    int unsigned NOT NULL,
  "OpeningBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "OpeningBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Debit' COMMENT 'Debit = customer owes us, Credit = we owe customer',
  "PendingBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "PendingBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Debit',
  "Notes"          varchar(255) DEFAULT NULL,
  "IsActive"       tinyint(1) NOT NULL DEFAULT '1',
  "IsDeleted"      tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy"      int unsigned NOT NULL,
  "UpdatedBy"      int unsigned NOT NULL,
  "CreatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("OpeningBalUID"),
  UNIQUE KEY "uq_cust_ob" ("OrgUID","CustomerUID"),
  KEY "idx_cob_customer" ("CustomerUID"),
  CONSTRAINT "fk_cob_customer" FOREIGN KEY ("CustomerUID") REFERENCES "CustomerTbl" ("CustomerUID") ON DELETE CASCADE
);

-- Customers.CustYearOpeningBalanceTbl definition
-- One row per customer per financial year — year-start opening balance snapshot.
-- Written once per year (insert-only on subsequent edits within the same year).

CREATE TABLE "CustYearOpeningBalanceTbl" (
  "YearBalUID"     int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID"         int unsigned NOT NULL,
  "CustomerUID"    int unsigned NOT NULL,
  "FinancialYear"  smallint NOT NULL COMMENT 'e.g. 2025 means FY 2025-26',
  "OpeningBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "OpeningBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Debit' COMMENT 'Debit = customer owes us, Credit = we owe customer',
  "Notes"          varchar(255) DEFAULT NULL,
  "IsActive"       tinyint(1) NOT NULL DEFAULT '1',
  "IsDeleted"      tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy"      int unsigned NOT NULL,
  "UpdatedBy"      int unsigned NOT NULL,
  "CreatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("YearBalUID"),
  UNIQUE KEY "uq_cyob_cust_year" ("OrgUID","CustomerUID","FinancialYear"),
  KEY "idx_cyob_customer" ("CustomerUID"),
  KEY "idx_cyob_org_year" ("OrgUID","FinancialYear"),
  CONSTRAINT "fk_cyob_customer" FOREIGN KEY ("CustomerUID") REFERENCES "CustomerTbl" ("CustomerUID") ON DELETE CASCADE
);

-- Vendors.VendOpeningBalanceTbl definition
-- One row per vendor — stores the cumulative running opening balance.
-- Sign convention: Credit = we owe vendor (+), Debit = vendor owes us (-).

CREATE TABLE "VendOpeningBalanceTbl" (
  "VendBalUID"     int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID"         int unsigned NOT NULL,
  "VendorUID"      int unsigned NOT NULL,
  "OpeningBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "OpeningBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Credit' COMMENT 'Credit = we owe vendor, Debit = vendor owes us',
  "PendingBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "PendingBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Credit',
  "Notes"          varchar(255) DEFAULT NULL,
  "IsActive"       tinyint(1) NOT NULL DEFAULT '1',
  "IsDeleted"      tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy"      int unsigned NOT NULL,
  "UpdatedBy"      int unsigned NOT NULL,
  "CreatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("VendBalUID"),
  UNIQUE KEY "uq_vend_ob" ("OrgUID","VendorUID"),
  KEY "idx_vob_vendor" ("VendorUID"),
  CONSTRAINT "fk_vob_vendor" FOREIGN KEY ("VendorUID") REFERENCES "VendorTbl" ("VendorUID") ON DELETE CASCADE
);

-- Vendors.VendYearOpeningBalanceTbl definition
-- One row per vendor per financial year — year-start opening balance snapshot.
-- Written once per year (insert-only on subsequent edits within the same year).

CREATE TABLE "VendYearOpeningBalanceTbl" (
  "YearBalUID"     int unsigned NOT NULL AUTO_INCREMENT,
  "OrgUID"         int unsigned NOT NULL,
  "VendorUID"      int unsigned NOT NULL,
  "FinancialYear"  smallint NOT NULL COMMENT 'e.g. 2025 means FY 2025-26',
  "OpeningBalance" decimal(15,2) NOT NULL DEFAULT '0.00',
  "OpeningBalType" enum('Debit','Credit') NOT NULL DEFAULT 'Credit' COMMENT 'Credit = we owe vendor, Debit = vendor owes us',
  "Notes"          varchar(255) DEFAULT NULL,
  "IsActive"       tinyint(1) NOT NULL DEFAULT '1',
  "IsDeleted"      tinyint(1) NOT NULL DEFAULT '0',
  "CreatedBy"      int unsigned NOT NULL,
  "UpdatedBy"      int unsigned NOT NULL,
  "CreatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn"      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("YearBalUID"),
  UNIQUE KEY "uq_vyob_vend_year" ("OrgUID","VendorUID","FinancialYear"),
  KEY "idx_vyob_vendor" ("VendorUID"),
  KEY "idx_vyob_org_year" ("OrgUID","FinancialYear"),
  CONSTRAINT "fk_vyob_vendor" FOREIGN KEY ("VendorUID") REFERENCES "VendorTbl" ("VendorUID") ON DELETE CASCADE
);

-- Users.PasswordResetTbl definition
-- One row per password-reset request. Token expires after 15 minutes; IsUsed=1 after use.

CREATE TABLE "PasswordResetTbl" (
  "ResetUID"  int unsigned NOT NULL AUTO_INCREMENT,
  "UserUID"   int unsigned NOT NULL,
  "Token"     varchar(64)  NOT NULL,
  "ExpiresAt" datetime     NOT NULL,
  "IsUsed"    tinyint(1)   NOT NULL DEFAULT '0',
  "IPAddress" varchar(45)  DEFAULT NULL,
  "CreatedOn" datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("ResetUID"),
  UNIQUE KEY "uq_token"  ("Token"),
  KEY "idx_prt_user"     ("UserUID"),
  KEY "idx_prt_expires"  ("ExpiresAt"),
  CONSTRAINT "fk_prt_user" FOREIGN KEY ("UserUID")
    REFERENCES "UserTbl" ("UserUID") ON DELETE CASCADE
);