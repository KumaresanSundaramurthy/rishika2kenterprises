-- `Transaction`.TransactionsTbl definition

CREATE TABLE "TransactionsTbl" (
  "TransUID" bigint NOT NULL AUTO_INCREMENT,
  "OrgUID" int unsigned NOT NULL,
  "UniqueNumber" varchar(30) NOT NULL,
  "ModuleUID" mediumint unsigned NOT NULL,
  "PrefixUID" int DEFAULT NULL COMMENT 'FK TransactionPrefixTbl PrefixUID',
  "TransType" varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "TransNumber" varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "TransDate" date NOT NULL,
  "Status" enum('Draft','Pending','Accepted','Rejected','Converted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Draft' COMMENT 'Quotation lifecycle status',
  "ConvertedToInvoiceUID" int DEFAULT NULL COMMENT 'FK → TransactionsTbl.TransUID of the resulting invoice',
  "QuotationType" varchar(50) DEFAULT NULL,
  "DispatchFrom" varchar(100) DEFAULT NULL,
  "ReferenceDetails" varchar(100) DEFAULT NULL,
  "SubTotal" decimal(15,4) NOT NULL DEFAULT '0.0000',
  "FinancialYear" smallint GENERATED ALWAYS AS (year(`TransDate`)) STORED NOT NULL,
  "PartyUID" int unsigned NOT NULL,
  "PartyType" enum('C','S','E') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'C',
  "StatusUID" tinyint unsigned NOT NULL DEFAULT '1',
  "GrossAmount" decimal(15,2) DEFAULT '0.00',
  "TaxableAmount" decimal(15,2) DEFAULT '0.00',
  "CgstAmount" decimal(15,2) DEFAULT '0.00',
  "SgstAmount" decimal(15,2) DEFAULT '0.00',
  "IgstAmount" decimal(15,2) DEFAULT '0.00',
  "DiscountAmount" decimal(15,2) DEFAULT '0.00',
  "AdditionalCharges" decimal(15,4) NOT NULL DEFAULT '0.0000',
  "TaxAmount" decimal(15,4) NOT NULL DEFAULT '0.0000',
  "RoundOff" decimal(8,4) NOT NULL DEFAULT '0.0000',
  "NetAmount" decimal(15,2) DEFAULT '0.00',
  "IsFullyPaid" bit(1) NOT NULL DEFAULT b'0',
  "PaidAmount" decimal(15,2) DEFAULT '0.00',
  "BalanceAmount" decimal(15,2) DEFAULT '0.00',
  "GlobalDiscPercent" bit(1) NOT NULL DEFAULT b'0',
  "ExtraDiscApplied" bit(1) NOT NULL DEFAULT b'0',
  "ExtraDiscType" varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "ExtraDiscAmount" decimal(15,2) DEFAULT '0.00',
  "IsDeleted" bit(1) NOT NULL DEFAULT b'0',
  "IsActive" bit(1) NOT NULL DEFAULT b'1',
  "CreatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "UpdatedBy" mediumint unsigned NOT NULL DEFAULT '1',
  "CreatedOn" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "UpdatedOn" datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ("TransUID","FinancialYear"),
  UNIQUE KEY "uidx_trans_number_year" ("FinancialYear","TransNumber"),
  UNIQUE KEY "uidx_unique_number_year" ("FinancialYear","UniqueNumber"),
  KEY "TransactionsTbl_MainQuery" ("ModuleUID","TransDate","StatusUID"),
  KEY "TransactionsTbl_PartyQuery" ("ModuleUID","PartyUID","TransDate"),
  KEY "TransactionsTbl_TransNumber" ("TransNumber"),
  KEY "TransactionsTbl_UniqueNumber" ("UniqueNumber"),
  KEY "TransactionsTbl_DateType" ("TransDate","TransType"),
  KEY "TransactionsTbl_TransDate_Status" ("TransDate","StatusUID"),
  KEY "TransactionsTbl_Party_Date_Amount" ("PartyUID","TransDate","NetAmount"),
  KEY "idx_transactions_active" ("IsDeleted","IsActive","TransUID"),
  KEY "idx_transactions_dates" ("TransDate"),
  KEY "idx_module_org" ("ModuleUID","OrgUID"),
  KEY "idx_module_status" ("ModuleUID","Status"),
  KEY "idx_converted_invoice" ("ConvertedToInvoiceUID")
)
/*!50100 PARTITION BY RANGE ("FinancialYear")
(PARTITION p2023 VALUES LESS THAN (2024),
 PARTITION p2024 VALUES LESS THAN (2025),
 PARTITION p2025 VALUES LESS THAN (2026),
 PARTITION p2026 VALUES LESS THAN (2027),
 PARTITION p_future VALUES LESS THAN MAXVALUE) */;


 -- `Transaction`.TransDetailTbl definition

CREATE TABLE "TransDetailTbl" (
  "FinancialYear" smallint NOT NULL,
  "TransUID" bigint NOT NULL,
  "ValidityDays" tinyint unsigned DEFAULT NULL,
  "ValidityDate" date DEFAULT NULL,
  "Reference" varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "Notes" text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  "TermsConditions" text,
  "ShippingAddress" json DEFAULT NULL,
  "AdditionalCharges" json DEFAULT NULL,
  "PaymentTerms" text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  "DeliveryDate" date DEFAULT NULL,
  "PlaceOfSupply" varchar(100) DEFAULT NULL,
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


-- `Transaction`.TransPageSettingsTbl definition

CREATE TABLE "TransPageSettingsTbl" (
  "TransPageSetgUID" int unsigned NOT NULL AUTO_INCREMENT,
  "ModuleUID" int unsigned NOT NULL,
  "DefaultPrefix" varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  "ShowFiscalYear" bit(1) NOT NULL DEFAULT b'1',
  "FiscalYearType" char(10) DEFAULT NULL,
  "InvoiceSepText" char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  "ValidityDays" smallint unsigned NOT NULL DEFAULT '30',
  PRIMARY KEY ("TransPageSetgUID")
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