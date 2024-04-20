function storeBiblo(name,data) {
    // Verificar si ya existen credenciales almacenadas
    if (localStorage.getItem(name)) {
        localStorage.removeItem(name);
    }
    localStorage.setItem(name, JSON.stringify(data));
}