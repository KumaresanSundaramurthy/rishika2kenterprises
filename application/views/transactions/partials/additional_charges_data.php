<script>
var _transAdditionalCharges  = <?php echo json_encode(array_values($AdditionalCharges   ?? [])); ?>;
var _transAdditionalTaxOpts  = <?php echo json_encode(array_values($TaxList             ?? [])); ?>;
var _transTransactionCharges = <?php echo json_encode(array_values($TransactionCharges  ?? [])); ?>;
</script>
<script src="/js/transactions/forms/additional_charges.js"></script>
