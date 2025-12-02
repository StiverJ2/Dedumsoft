document.getElementById("loginForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const errorMessage = document.getElementById("error-message");
    const loginButton = document.querySelector(".btn-login");

    errorMessage.textContent = "";

    if (!username || !password) {
        errorMessage.textContent = "Todos los campos son obligatorios.";
        return;
    }

    // Cambiar texto del botón durante la autenticación
    loginButton.textContent = "Ingresando...";
    loginButton.disabled = true;

    try {
        // Simular proceso de autenticación (reemplazar con tu API real)
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Aquí iría tu llamada real a la API:
        /*
        const response = await fetch("/api/login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (response.ok) {
            sessionStorage.setItem("authToken", data.token);
            window.location.href = "index.html";
        } else {
            errorMessage.textContent = data.message || "Usuario o contraseña incorrectos.";
        }
        */
        
        // Simulación de login exitoso para demostración
        if (username === "admin" && password === "admin") {
            sessionStorage.setItem("authToken", "simulated-token");
            window.location.href = "index.html";
        } else {
            errorMessage.textContent = "Usuario o contraseña incorrectos.";
        }

    } catch (error) {
        console.error("Error de conexión:", error);
        errorMessage.textContent = "Error de conexión con el servidor.";
    } finally {
        loginButton.textContent = "Ingresar";
        loginButton.disabled = false;
    }
});

// Efecto de focus mejorado
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });
});

// Funcionalidad para el enlace "Olvidé mi contraseña"
document.querySelector(".forgot-password").addEventListener("click", function(e) {
    e.preventDefault();
    alert("Función de recuperación de contraseña en desarrollo. Por favor, contacte al administrador.");
});