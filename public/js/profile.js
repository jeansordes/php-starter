document.addEventListener("DOMContentLoaded", function () {
    const usernameInput = document.getElementById("username");
    const usernameFormGroup = usernameInput.closest(".mb-3");
    let validationTimer;

    usernameInput.addEventListener("input", function () {
        clearTimeout(validationTimer);
        const username = this.value;

        // Clear previous feedback
        const existingFeedback = usernameFormGroup.querySelector(
            ".invalid-feedback, .valid-feedback"
        );
        if (existingFeedback) {
            existingFeedback.remove();
        }
        usernameInput.classList.remove("is-invalid", "is-valid");

        if (username.length === 0) {
            // Handle empty username case directly on input (optional, backend also checks)
            const feedback = document.createElement("div");
            feedback.classList.add("invalid-feedback");
            feedback.textContent = "Username cannot be empty.";
            usernameFormGroup.appendChild(feedback);
            usernameInput.classList.add("is-invalid");
            return;
        }

        // Set a timer to wait for the user to finish typing
        validationTimer = setTimeout(function () {
            // Use the globally defined baseUrl variable
            const validationUrl = baseUrl + "/profile/validate-username";
            const postBody = "username=" + encodeURIComponent(username);

            fetch(validationUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: postBody,
            })
                .then((response) => {
                    if (!response.ok) {
                        console.error("HTTP error!", response.status);
                        // Optionally throw an error to be caught by the catch block
                        // throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then((data) => {
                    const feedback = document.createElement("div");
                    if (data.valid) {
                        feedback.classList.add("valid-feedback");
                        feedback.textContent = "Username is available.";
                        usernameInput.classList.add("is-valid");
                    } else {
                        feedback.classList.add("invalid-feedback");
                        feedback.textContent = data.message;
                        usernameInput.classList.add("is-invalid");
                    }
                    usernameFormGroup.appendChild(feedback);
                })
                .catch((error) => {
                    console.error("Error during username validation:", error);
                    // Optionally display a generic error message
                });
        }, 500);
    });
});
