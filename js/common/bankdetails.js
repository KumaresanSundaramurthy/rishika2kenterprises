$(document).ready(function () {
    
    $('#addBankDetails').on('click', function(e) {
        e.preventDefault();
        $('#AddEditBankDataForm')[0].reset();
        $('#HBankId').val('');
        $('#addEditBankDataModal .AddEditBankDataBtn').text('Save');
        $('#addEditBankDataModal').modal('show');
    });

    $('#AddEditBankDataForm').on('submit', function(e) {
        e.preventDefault();

        const bankFields = [
            '#BankAccNumber','#ReEntBankAccNumber','#BankIFSC_Code','#BankBranchName','#BankAccHolderName'
        ];
        const upi = $('#UPITransfer_Id').val().trim();

        const bankFilled = bankFields.every(id => $(id).val().trim() !== '');
        const upiFilled  = upi !== '';

        if ((bankFilled && !upiFilled) || (!bankFilled && upiFilled) || (bankFilled && upiFilled)) {

            const accNumber = $('#BankAccNumber').val().trim();
            const reAccNumber = $('#ReEntBankAccNumber').val().trim();
            const ifsc        = $('#BankIFSC_Code').val().trim();
            const upi         = $('#UPITransfer_Id').val().trim();

            if (bankFilled && accNumber !== reAccNumber) {
                showBankDetailsError('Bank Account Number and Re-Enter Account Number must match.');
                return false;
            }

            if (bankFilled && !/^\d{9,18}$/.test(accNumber)) {
                showBankDetailsError('Account Number must be 9–18 digits.');
                return false;
            }

            if (bankFilled && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifsc)) {
                showBankDetailsError('Invalid IFSC Code format.');
                return false;
            }

            if (upiFilled && !/^[a-zA-Z0-9.\-_]{2,}@[a-zA-Z]{3,}$/.test(upi)) {
                showBankDetailsError('Invalid UPI ID format. Example: name@bank');
                return false;
            }

            let recordId = $('#HBankId').val().trim();
            if (recordId === '') {
                recordId = 'New-'+Date.now();
            }

            const record = {
                id: recordId,
                type: bankFilled ? 'Bank' : 'UPI',
                accNumber: accNumber,
                ifsc: $('#BankIFSC_Code').val().trim(),
                branch: $('#BankBranchName').val().trim(),
                holder: $('#BankAccHolderName').val().trim(),
                upiId: upi
            };

            const existingRow = $('#bankDetailsBody').find(`tr[data-id="${record.id}"]`);
            if (existingRow.length) {
                existingRow.data('record', record);
                existingRow.attr('data-record', JSON.stringify(record));
                existingRow.find('td:eq(0)').text(record.type);
                existingRow.find('td:eq(1)').text(record.type === 'Bank' ? record.accNumber : record.upiId);
                existingRow.find('td:eq(2)').text(record.type === 'Bank' ? record.ifsc : '-');
                existingRow.find('td:eq(3)').text(record.type === 'Bank' ? record.branch : '-');
                existingRow.find('td:eq(4)').text(record.type === 'Bank' ? record.holder : '-');
            } else {
                const rowHtml = `
                    <tr data-id="${record.id}" data-type="${record.type}" data-record='${JSON.stringify(record)}'>
                    <td>${record.type}</td>
                    <td>${record.type === 'Bank' ? record.accNumber : record.upiId}</td>
                    <td>${record.type === 'Bank' ? record.ifsc : '-'}</td>
                    <td>${record.type === 'Bank' ? record.branch : '-'}</td>
                    <td>${record.type === 'Bank' ? record.holder : '-'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary me-1 editBankDataBtn"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-danger deleteBankDataBtn"><i class="bx bx-trash"></i></button>
                    </td>
                    </tr>`;
                $('#bankDetailsBody').append(rowHtml);
            }

            $('#appendBankDetails').removeClass('d-none');
            $('#bankDivider').removeClass('d-none');
            $('#addEditBankDataModal').modal('hide');
        } else {
            showBankDetailsError('Fill either all Bank fields OR UPI ID OR both. Partial Bank info not allowed.');
        }
    });

    $(document).on('click', '.editBankDataBtn', function(e) {
        e.preventDefault();

        const row = $(this).closest('tr');
        const record = row.data('record');

        if (record.type === 'Bank') {
            $('#AddEditBankDataForm #BankAccNumber').val(record.accNumber || '');
            $('#AddEditBankDataForm #ReEntBankAccNumber').val(record.accNumber || '');
            $('#AddEditBankDataForm #BankIFSC_Code').val(record.ifsc || '');
            $('#AddEditBankDataForm #BankBranchName').val(record.branch || '');
            $('#AddEditBankDataForm #BankAccHolderName').val(record.holder || '');
            $('#AddEditBankDataForm #UPITransfer_Id').val('');
        } else {
            $('#AddEditBankDataForm #UPITransfer_Id').val(record.upiId || '');
            $('#AddEditBankDataForm #BankAccNumber,#AddEditBankDataForm #ReEntBankAccNumber,#AddEditBankDataForm #BankIFSC_Code,#AddEditBankDataForm #BankBranchName,#AddEditBankDataForm #BankAccHolderName').val('');
        }

        $('#AddEditBankDataForm #HBankId').val(record.id || '');
        $('#addEditBankDataModal .AddEditBankDataBtn').text('Update');
        $('#addEditBankDataModal').modal('show');

    });

    $(document).on('click', '.deleteBankDataBtn', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        const recordId = row.data('id');
        Swal.fire({
            title: "Do you want to delete the bank details?",
            text: "You won't be able to revert this!",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
            cancelButtonColor: "#3085d6",
        }).then((result) => {
            if (result.isConfirmed) {
                if (recordId > 0) {
                    delBankData.push(recordId);
                    delBankDataFlag = 1;
                }
                row.remove();
                if ($('#bankDetailsBody').children().length === 0) {
                    $('#appendBankDetails').addClass('d-none');
                    $('#bankDivider').addClass('d-none');
                }
            }
        });
    });
    
});

function getBankRecordsFromTable() {
    const records = [];
    $('#bankDetailsBody tr').each(function () {
        const $tr = $(this);
        // Prefer data-record if present, else reconstruct from cells
        const raw = $tr.attr('data-record');
        if (raw) {
            try {
                const rec = JSON.parse(raw);
                records.push(rec);
                return;
            } catch (e) { /* fall through */ }
        }
        const type = ($tr.attr('data-type') || $tr.find('td:eq(0)').text()).trim();
        const col1 = $tr.find('td:eq(1)').text().trim();
        const ifsc = $tr.find('td:eq(2)').text().trim();
        const branch = $tr.find('td:eq(3)').text().trim();
        const holder = $tr.find('td:eq(4)').text().trim();
        records.push({
            id: $tr.data('id') ? String($tr.data('id')) : String(Date.now()),
            type: type,
            accNumber: type === 'Bank' ? col1 : '',
            ifsc: type === 'Bank' ? ifsc : '',
            branch: type === 'Bank' ? branch : '',
            holder: type === 'Bank' ? holder : '',
            upiId: type === 'UPI' ? col1 : ''
        });
    });
    return records;
}

function validateBankRecords(records) {
    if (!records.length) return { ok: true };

    for (const r of records) {
        const bankFilled = r.type === 'Bank' && r.accNumber && r.ifsc && r.branch && r.holder;
        const upiFilled = r.type === 'UPI' && r.upiId;

        // Enforce strict type content
        if (r.type === 'Bank' && !bankFilled) {
        return { ok: false, msg: 'Incomplete Bank record in table. Please complete all Bank fields or remove the row.' };
        }
        if (r.type === 'UPI' && !upiFilled) {
        return { ok: false, msg: 'Incomplete UPI record in table. Please provide a valid UPI ID or remove the row.' };
        }
    }

    return { ok: true };
}

function showBankDetailsError(message) {
    showAlertMessageSwal('error', '', message, true, 2000);
}