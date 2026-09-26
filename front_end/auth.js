//add an event listener to the login form to handle the login process
const loginForm = document.getElementById("loginForm");
if (loginForm) {
    loginForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        //get the login and password values from the form
        const login = document.getElementById("login").value.trim();
        const password = document.getElementById("password").value.trim();

        //clear any previous error messages and validate the form values
        clearError("login");
        clearError("password");
        if (login === "") {
            showError("login", "Username is required.");
            return;
        }
        if (password === "") {
            showError("password", "Password is required.");
            return;
        }

        //send a POST request to the server with the login and password
        const response = await fetch("api/login.php", {
            method: "POST",
            headers: {
               "Content-Type": "application/json"
            },
            body: JSON.stringify({
                login: login,
                password: password
            })
        });

        //parse the response from the server
        const data = await response.json();

        //if the login was successful, store the user information and route by role
        if (response.ok) { 
            let minutes = 20;
            let date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));

            document.cookie = "firstName=" + data.user.FirstName +
                                ",lastName=" + data.user.LastName +
                                ",userId=" + data.user.ID +
                                ";expires=" + date.toGMTString() +
                                ";path=/";

            // Redirect based on the user's role from the database
            if (data.user.Role === "Admin") {
                window.location.href = "admin.html";
            } else {
                window.location.href = "contacts.html";
            }
        } else {
            // Displays the generic error directly on the login form instead of a popup
            showError("password", data.error); 
            showError("login", "Login failed.");
        }
    });
}

//add an event listener to the registration form to handle the registration process
const registerForm = document.getElementById("registerForm");
if (registerForm) {
    registerForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        //get the form values
        const firstName = document.getElementById("firstName").value.trim();
        const lastName = document.getElementById("lastName").value.trim();
        const login = document.getElementById("login").value.trim();
        const password = document.getElementById("password").value.trim();
        const confirmPassword = document.getElementById("confirmPassword").value.trim();

        //clear any previous error messages and validate the form values
        clearError("firstName");
        clearError("lastName");
        clearError("login");
        clearError("password");
        clearError("confirmPassword");

        if (firstName === "") {
            showError("firstName", "First name is required.");
            return;
        }
        if (lastName === "") {
            showError("lastName", "Last name is required.");
            return;
        }
        if (login === "") {
            showError("login", "Username is required.");
            return;
        }
        if (password === "") {
            showError("password", "Password is required.");
            return;
        }
        if (password.length < 6) {
            showError("password", "Password must be at least 6 characters.");
            return;
        }
        if (confirmPassword === "") {
            showError("confirmPassword", "Please confirm your password.");
            return;
        }
        if (password !== confirmPassword) {
            showError("confirmPassword", "Passwords do not match.");
            return;
        }

        //send a POST request to the server with the registration information
        const response = await fetch("api/register.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                firstName: firstName,
                lastName: lastName,
                login: login,
                password: password
            })
        });

        //parse the response from the server
        const data = await response.json();

        //if the registration was successful, redirect to the login page
        if (data.error === "") {
            window.location.href = "index.html";
        } else {
            alert(data.error);
        }
    });
}

//function to show error messages and styling for form inputs
function showError(inputId, message) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(inputId + "Error");

    if (input && error) {
        input.classList.add("inputError");
        error.innerHTML = message;
        error.style.display = "block";
    }
}
//function to clear error messages and styling from form inputs
function clearError(inputId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(inputId + "Error");

    if (input && error) {
        input.classList.remove("inputError");
        error.innerHTML = "";
        error.style.display = "none";
    }
}