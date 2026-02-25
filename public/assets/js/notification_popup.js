document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("customerForm");

    /**
     * Function to trigger hardware indication (LED/Buzzer)
     * @param {string} status - 'success' or 'error'
     */
    async function triggerHardwareIndication(status) {
        try {
            const response = await fetch('/api/hardware/indicate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status: status })
            });

            const data = await response.json();
            
            if (!response.ok) {
                console.error('Hardware indication failed:', data.message);
            } else {
                console.log('Hardware indication activated:', data.message);
            }
        } catch (error) {
            console.error('Error calling hardware API:', error);
            // Don't block the UI if hardware fails
        }
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        let fname = document.getElementById("fname").value;
        let lname = document.getElementById("lname").value;
        let phone = document.getElementById("phone").value;
        let address = document.getElementById("address").value;
        let email = document.getElementById("email").value;

        // Validate form data
        if (!fname || !lname || !phone || !address || !email) {
            alert("Please fill in all required fields!");
            triggerHardwareIndication('error');
            return;
        }

        try {
            // TODO: Replace this with actual database API call when implementing database saving
            // Example:
            // const response = await fetch('/api/customers', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ fname, lname, phone, address, email })
            // });
            // if (!response.ok) {
            //     throw new Error('Failed to save customer');
            // }

            // For now, just add to table (existing notification logic)
            let table = document.getElementById("customerTable");

            let row = document.createElement("tr");

            row.innerHTML = `
                <td>${fname}</td>
                <td>${lname}</td>
                <td>${phone}</td>
                <td>${address}</td>
                <td>${email}</td>
            `;

            table.appendChild(row);

            alert("Customer added successfully!");

            // Trigger success indication (blue LED)
            triggerHardwareIndication('success');

            form.reset();
        } catch (error) {
            // Handle errors (e.g., database save failure)
            console.error('Error saving customer:', error);
            alert("Error: Failed to save customer. Please try again.");
            // Trigger error indication (red LED + buzzer)
            triggerHardwareIndication('error');
        }
    });

});