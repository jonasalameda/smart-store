document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("customerForm");

    /**
     * Function to trigger hardware indication (LED/Buzzer)
     * @param {string} status - 'success' or 'error'
     */
    async function triggerHardwareIndication(status) {
        try {
            const response = await fetch('/smart-store/api/hardware/indicate', {
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

        //Make the variables same as in the form
        const first_name = document.getElementById("first_name").value.trim();
        const last_name = document.getElementById("last_name").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const address = document.getElementById("address").value.trim();
        const email = document.getElementById("email").value.trim();

        // Validate form data   //-Emmanuel we dont need this because i added required, meaning any of the field cannot emptied 
        
        
        //Mariam: I will Remove 'required' since we want server-side validation to trigger the LEDs, not in-browser validation


        // if (!first_name || !last_name || !phone || !address || !email) {
        //     event.preventDefault();
        //     alert("Please fill in all required fields!");
        //     triggerHardwareIndication('error');
        //     return;
        // }

        // try {
        //     // TODO: Replace this with actual database API call when implementing database saving
        //     // Example:
        //     // const response = await fetch('/api/customers', {
        //     //     method: 'POST',
        //     //     headers: { 'Content-Type': 'application/json' },
        //     //     body: JSON.stringify({ fname, lname, phone, address, email })
        //     // });
        //     // if (!response.ok) {
        //     //     throw new Error('Failed to save customer');
        //     // }

        //     // For now, just add to table (existing notification logic)
        //     let table = document.getElementById("customerTable");

        //     let row = document.createElement("tr");

        //     row.innerHTML = `
        //         <td>${fname}</td>
        //         <td>${lname}</td>
        //         <td>${phone}</td>
        //         <td>${address}</td>
        //         <td>${email}</td>
        //     `;

        //     table.appendChild(row);

        //     alert("Customer added successfully!");

        //     // Trigger success indication (blue LED)
        //     triggerHardwareIndication('success');

        //     form.reset();
        // } catch (error) {
        //     // Handle errors (e.g., database save failure)
        //     console.error('Error saving customer:', error);
        //     alert("Error: Failed to save customer. Please try again.");
        //     // Trigger error indication (red LED + buzzer)
        //     triggerHardwareIndication('error');
        // }

        const nameRegex = /^[A-Za-z]{2,50}$/;
        const phoneRegex = /^\d{10}$/;
        const addressRegex = /^.{0,50}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!first_name || !last_name || !phone || !email) {
            await triggerHardwareIndication('error');
            alert("Please fill in all required fields!");
            return;
        }

        if (!nameRegex.test(first_name) || !nameRegex.test(last_name)) {
            await triggerHardwareIndication('error');
            alert("Names must be 2-50 characters only.");
            return;
        }

        if (!phoneRegex.test(phone)) {
            await triggerHardwareIndication('error');
            alert("Phone must be exactly 10 digits, with only numbers");
            return;
        }

        if (!addressRegex.test(address)) {
            await triggerHardwareIndication('error');
            alert("Address be maximum 50 characters.");
            return;
        }

        if (!emailRegex.test(email)) {
            await triggerHardwareIndication('error');
            alert("Email must have the format abc@example.com");
            return;
        }
        //submit the form to PHP after all validation so we can trigger pythhon
        form.submit();
    });
});