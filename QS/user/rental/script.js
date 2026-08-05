
            // Helper function to get value by ID
            function getValue(id) {
                return document.getElementById(id).value;
            }

            function handleResponse(response) {
                // Assuming response is JSON
                try {
                    var responseData = JSON.parse(response);

                    if (responseData.success) {
                        displaySuccessModal();
                    } else {
                        displayErrorModal(responseData.errorMessage);
                    }
                } catch (error) {
                    console.error("Error parsing JSON response:", error);
                    // Handle the error if needed
                }
            }

            function displaySuccessModal() {
                Swal.fire({
                    title: 'Success',
                    text: 'Record updated successfully',
                    icon: 'success'
                }).then((value) => {
                    // Redirect or perform any other action after the modal is closed
                    window.location.href = 'created_contract.php';
                });
            }

            function displayErrorModal(errorMessage) {
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error'
                }).then((value) => {
                    // Handle the error or redirect if needed
                });
            }


            // Helper function to get input values by ID
            function getValue(elementId) {
                return document.getElementById(elementId).value;
            }


            function closeModal() {
                var modal = document.getElementById('editModal');
                modal.style.display = 'none';
            }
            function highlightRow(row) {
                // Get the selected ID input element
                var selectedIdInput = document.getElementById('selected_id_display');
            
                // Highlight the clicked row
                row.style.backgroundColor = '#f7f0f0';
            
                // Get and display the ID
                var selectedId = row.querySelector('td:first-child').innerText;
            
                // Check if the selected ID is already the same as the current one
                var currentId = selectedIdInput.value;
    
                var editBtn = row.querySelector('button[name="edit_contract"]');
                var sendBtn = row.querySelector('button[name="send_contract"]');

                if(editBtn.style.visibility === 'visible') {
                    editBtn.style.visibility = 'hidden';
                    sendBtn.style.visibility = 'hidden';
                }else {
                    selectedIdInput.value = selectedId;
                    editBtn.style.visibility = 'visible';
                    sendBtn.style.visibility = 'visible';
                }

                // if (selectedId === currentId) {
                //     selectedIdInput.value = ''; // Use 'value' instead of 'textContent'
                //     // Clicked the same row twice, empty the selected ID value and hide buttons
                //     if (editBtn) {
                //         editBtn.style.visibility = 'hidden';
                //     }
            
                //     if (sendBtn) {
                //         sendBtn.style.visibility = 'hidden';
                //     }

                //     console.log('same id, id: ' + selectedIdInput.value);
                // } else {
                //     // Clicked a different row, update the selected ID and display buttons
                //     selectedIdInput.value = selectedId; // Use 'value' instead of 'textContent'
            
                //     // Find and display the hidden buttons within the current row
                //     if (editBtn) {
                //         editBtn.style.visibility = 'visible';
                //     }
            
                //     if (sendBtn) {
                //         sendBtn.style.visibility = 'visible';
                //     }

                //     console.log('different id, id: ' + selectedIdInput.value);
                // }
            
                // Remove any existing highlights and visibility from other rows
                var table = row.closest('table');
                var rows = table.querySelectorAll('tr');
                for (var i = 1; i < rows.length; i++) {
                    if (rows[i] !== row) {
                        rows[i].style.backgroundColor = '';
                        var editBtn = rows[i].querySelector('button[name="edit_contract"]');
                        if (editBtn) {
                            editBtn.style.visibility = 'hidden';
                        }
                        var sendBtn = rows[i].querySelector('button[name="send_contract"]');
                        if (sendBtn) {
                            sendBtn.style.visibility = 'hidden';
                        }
                    }
                }
            }
            
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-note');
            const modalNoteContent = document.getElementById('auditNoteContent');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const note = this.getAttribute('data-note');
                    modalNoteContent.textContent = note;
                    document.getElementById('noteModal').style.display = 'block'; // Show the modal
                });
            });
            
            // Close modal when close button or outside modal is clicked
            document.querySelectorAll('.modalNote .btn-close, .modalNote').forEach(element => {
                element.addEventListener('click', function(event) {
                    if (event.target === this) {
                        document.getElementById('noteModal').style.display = 'none';
                    }
                });
            });
        });
        function calculateVat() {
            var amount = parseFloat(document.getElementById('amount').value);
            var selectVat = document.getElementById('select_vat');
            var wTaxTypeLbl = document.getElementById('wtaxType_lbl');
            var selectWtax = document.getElementById('select_wtax');
            var selectPercent = document.getElementById('select_percent');
            var netOfVat_lbl = document.getElementById('netOfVat_lbl');
            var net_Of_Vat = document.getElementById('net_of_vat');
            var amount_comp_lbl = document.getElementById('amountComp_lbl');
            var amount_comp = document.getElementById('amountComp');
            var netOfVat = document.getElementById('vat');
            var vatLbl = document.getElementById('vat_lbl');
            var netOfWtax = document.getElementById('w-tax');
            var wTaxLbl = document.getElementById('wtax_lbl');
            var grossAmountInput = document.getElementById('gross_amount');
            var grossLbl = document.getElementById('gross_lbl');
            var amountToLessor = document.getElementById('amount_lessor');
            var editAmountToLessor = document.getElementById('edit_amount_lessor');
            var amountToLessorLbl = document.getElementById('amount_lessor_lbl');

            if (!isNaN(amount) && amount > 0) {
                selectVat.disabled = false;
                selectWtax.disabled = false;
                selectPercent.disabled = false;

                if (selectVat.value === "Vatable") {
                    Swal.fire({
                        title: 'Is the amount inputted with VAT or without VAT?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'With VAT',
                        cancelButtonText: 'Without VAT',
                        reverseButtons: true
                    }).then((result) => {
                        var vatAmount = 0, netOf_Vat = 0;
                        if (result.isConfirmed) { // With VAT
                            netOf_Vat = amount / 1.12;
                            vatAmount = amount - netOf_Vat;
                            selectWtax.style.display = 'block';
                            wTaxTypeLbl.style.display = 'block';
                            net_Of_Vat.value = netOf_Vat.toFixed(2);

                            // Display selected choice
                            amount_comp_lbl.style.display = 'block';
                            amount_comp.value = 'With VAT';

                        } else { // Without VAT
                            vatAmount = amount * 0.12;
                            netOf_Vat = amount;

                            selectWtax.style.display = 'block';
                            wTaxTypeLbl.style.display = 'block';
                            net_Of_Vat.value = netOf_Vat.toFixed(2);

                            // Display selected choice
                            amount_comp_lbl.style.display = 'block';
                            amount_comp.value = 'Without VAT';
                        }

                        netOfVat.value = vatAmount.toFixed(2); // VAT amount display

                        netOfVat.style.display = 'block';
                        vatLbl.style.display = 'block';

                        netOfVat_lbl.style.display = 'block';
                        net_Of_Vat.style.display = 'block';

                        amount_comp_lbl.style.display = 'block';
                        amount_comp.style.display = 'block';

                        // Proceed to calculate wtax based on VAT
                        calculateWtax(vatAmount);
                    });
                }else if(selectVat.value === "Non Vatable"){
                        vat = 0;
                        netVat = 0;
                        
                        net_Of_Vat.value = vat.toFixed(2);
                        netOfVat.value = vat.toFixed(2);

                        netOfVat.style.display = 'block';
                        vatLbl.style.display = 'block';

                        netOfVat_lbl.style.display = 'block';
                        net_Of_Vat.style.display = 'block';

                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                }
                else if(selectVat.value === "Vat Exempt"){
                        vat = 0;
                        netVat = 0;
                        
                        net_Of_Vat.value = vat.toFixed(2);
                        netOfVat.value = vat.toFixed(2);

                        netOfVat.style.display = 'block';
                        vatLbl.style.display = 'block';

                        netOfVat_lbl.style.display = 'block';
                        net_Of_Vat.style.display = 'block';

                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                }
            }
        }

        function calculateWtax(vatAmount) {
            var amount = parseFloat(document.getElementById('amount').value);
            var selectVat = document.getElementById('select_vat');
            var selectWtax = document.getElementById('select_wtax');
            var percentLbl = document.getElementById('percent_lbl');
            var selectPercent = document.getElementById('select_percent');
            var amount_comp_lbl = document.getElementById('amountComp_lbl');
            var amount_comp = document.getElementById('amountComp');
            var netOfVat_lbl = document.getElementById('netOfVat_lbl');
            var net_Of_Vat = parseFloat(document.getElementById('net_of_vat').value);
            var netOfVat = parseFloat(document.getElementById('vat').value);
            var vatLbl = document.getElementById('vat_lbl');
            var netOfWtax = document.getElementById('w-tax');
            var wTaxLbl = document.getElementById('wtax_lbl');
            var grossAmountInput = document.getElementById('gross_amount');
            var grossLbl = document.getElementById('gross_lbl');
            var amountToLessor = document.getElementById('amount_lessor');
            var editAmountToLessor = document.getElementById('edit_amount_lessor');
            var amountToLessorLbl = document.getElementById('amount_lessor_lbl');
            if(selectWtax.value === 'less_wtax' || selectWtax.value === 'net_wtax'){
                selectPercent.style.display = 'block';
                percentLbl.style.display = 'block';
            }
            if (!isNaN(amount) && amount > 0) {
                var vatAmount = 0, wtax = 0, netAmount = 0, totalExp = 0, vatPlusAmount = 0;

                if (selectWtax.value === "less_wtax" && selectPercent.value === "5") {
                    wtax = net_Of_Vat * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';
                    if(amount_comp.value === 'With VAT'){
                        var totalExp = amount + wtax;
                    }else{
                        var totalExp = net_Of_Vat + netOfVat + wtax;
                    }
                    grossAmountInput.value = totalExp.toFixed(2);
                    grossAmountInput.style.display = 'block';
                    grossLbl.style.display = 'block';

                    netAmount = amount - wtax;// amount to lessor display

                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);

                    amountToLessor.style.display = 'none';
                    editAmountToLessor.style.display = 'block';
                    amountToLessorLbl.style.display = 'block';

                    
                }else if(selectWtax.value === "net_wtax" && selectPercent.value === "5") {
                    wtax = net_Of_Vat * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    if(amount_comp.value === 'With VAT'){
                        var totalExp = amount + wtax;
                    }else{
                        var totalExp = net_Of_Vat + netOfVat + wtax;
                    }
                    grossAmountInput.value = totalExp.toFixed(2);

                    grossAmountInput.style.display = 'block';
                    grossLbl.style.display = 'block';


                    if(amount_comp.value === 'Without VAT'){
                        netAmount = net_Of_Vat + netOfVat;
                        amountToLessor.value = netAmount.toFixed(2);
                        editAmountToLessor.value = netAmount.toFixed(2);
                    }else{
                        netAmount = amount;// amount to lessor display
                        amountToLessor.value = netAmount.toFixed(2);
                        editAmountToLessor.value = netAmount.toFixed(2);
                    }
                    

                    amountToLessor.style.display = 'none';
                    editAmountToLessor.style.display = 'block';
                    amountToLessorLbl.style.display = 'block';
                }
                if(selectVat.value === "Non Vatable" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                    wtax = amount * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount - wtax; // Net amount is gross amount minus withholding tax
                    var totalExp = amount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }else if(selectVat.value === "Non Vatable" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){

                    wtax_amount = amount / 0.95; // Calculate wtax based on amount minus VAT
                    wtax = wtax_amount * 0.05;
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount;
                    var totalExp = amount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }
                if(selectVat.value === "Vat Exempt" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                    wtax = amount * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount - wtax; // Net amount is gross amount minus withholding tax
                    var totalExp = amount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }else if(selectVat.value === "Vat Exempt" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){

                    wtax_amount = amount / 0.95; // Calculate wtax based on amount minus VAT
                    wtax = wtax_amount * 0.05;
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount;
                    var totalExp = amount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }
            }
        }

         document.addEventListener('DOMContentLoaded', function () {
    // Handle single file click (open directly in new tab)
    document.querySelectorAll('.view-single-file').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const fileContent = event.target.dataset.fileContent;
            const mimeType = event.target.dataset.mimeType;
            const fileName = event.target.dataset.fileName;

            // Convert base64 to binary string
            const binaryString = atob(fileContent);

            // Create a Uint8Array from the binary string
            const uint8Array = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }

            // Create a Blob from the Uint8Array
            const blob = new Blob([uint8Array], { type: mimeType });

            // Create a URL for the Blob and use it to open in a new tab
            const blobUrl = URL.createObjectURL(blob);

            // Open the blob URL in a new tab
            window.open(blobUrl, '_blank');
        });
    });

    // Handle multiple files (display in modal)
    document.querySelectorAll('.view-contracts').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            const files = JSON.parse(event.target.dataset.contractFiles);
            const filePreview = document.getElementById('filePreview');

            // Clear previous file links
            filePreview.innerHTML = '';

            // Add each file link to the modal
            files.forEach(function (file) {
                const linkElement = document.createElement('a');
                linkElement.href = '#';
                linkElement.innerHTML = file.icon + ' ' + file.file;
                linkElement.style.display = 'block';
                linkElement.dataset.fileContent = file.content;
                linkElement.dataset.mimeType = file.mimeType;
                linkElement.dataset.fileName = file.file;

                linkElement.addEventListener('click', function (event) {
                    event.preventDefault();
                    const fileContent = event.target.dataset.fileContent;
                    const mimeType = event.target.dataset.mimeType;
                    const fileName = event.target.dataset.fileName;

                    // Convert base64 to binary string
                    const binaryString = atob(fileContent);

                    // Create a Uint8Array from the binary string
                    const uint8Array = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        uint8Array[i] = binaryString.charCodeAt(i);
                    }

                    // Create a Blob from the Uint8Array
                    const blob = new Blob([uint8Array], { type: mimeType });

                    // Create a URL for the Blob and use it to open in a new tab
                    const blobUrl = URL.createObjectURL(blob);

                    // Open the blob URL in a new tab
                    window.open(blobUrl, '_blank');
                });

                filePreview.appendChild(linkElement);
            });

            // Show the modal
            document.getElementById('fileModal').style.display = 'block';
        });
    });

    // Close modal when the close button is clicked
    document.querySelector('.file-modal-close').addEventListener('click', function () {
        document.getElementById('fileModal').style.display = 'none';
    });

    // Close modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('fileModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});






                