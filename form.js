   document.getElementById('contactForm').addEventListener('submit', async function(e) {
       e.preventDefault();
       
       const formData = new FormData(this);
       
       try {
           const response = await fetch('process_form.php', {
               method: 'POST',
               body: formData
           });
           
           const result = await response.json();
           
           if (result.success) {
               alert(result.message);
               this.reset();
           } else {
               alert(result.message);
           }
       } catch (error) {
           alert('Error submitting form. Please try again later.');
       }
   });
   