/**
 * VERSION DEBUG - Ubigeo Manager
 * Versión simplificada para debugging
 */

console.log('=== UBIGEO DEBUG VERSION LOADED ===');

let ubigeoData = null;

// Función simple para cargar y testear
async function loadAndTestUbigeo() {
    console.log('1. Iniciando carga...');
    
    try {
        console.log('2. Haciendo fetch...');
        const response = await fetch('assets/data/ubigeo_peru.json');
        console.log('3. Respuesta recibida:', response.ok);
        
        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status);
        }
        
        ubigeoData = await response.json();
        console.log('4. Datos cargados:', ubigeoData.departments.length, 'departamentos');
        
        // Poblar inmediatamente
        populateDepartmentsDebug();
        
    } catch (error) {
        console.error('ERROR cargando ubigeo:', error);
    }
}

function populateDepartmentsDebug() {
    console.log('5. Poblando departamentos...');
    
    const departmentSelect = document.getElementById('state');
    console.log('6. Element found:', !!departmentSelect);
    
    if (!departmentSelect) {
        console.error('7. NO SE ENCONTRÓ EL SELECT DE DEPARTAMENTOS');
        return;
    }
    
    if (!ubigeoData) {
        console.error('8. NO HAY DATOS DE UBIGEO');
        return;
    }
    
    // Limpiar y habilitar
    departmentSelect.innerHTML = '<option value="">Seleccione un departamento</option>';
    departmentSelect.disabled = false;
    
    console.log('9. Select limpiado y habilitado');
    
    // Agregar departamentos
    ubigeoData.departments.forEach((department, index) => {
        const option = document.createElement('option');
        option.value = department.name;
        option.textContent = department.name;
        departmentSelect.appendChild(option);
        
        if (index < 3) {
            console.log('10. Agregado:', department.name);
        }
    });
    
    console.log('11. Total departamentos agregados:', ubigeoData.departments.length);
    
    // Agregar event listener
    departmentSelect.addEventListener('change', function() {
        console.log('12. CAMBIO EN DEPARTAMENTO:', this.value);
        if (this.value) {
            populateProvincesDebug(this.value);
        }
    });
    
    console.log('13. Event listener agregado');
}

function populateProvincesDebug(departmentName) {
    console.log('14. Poblando provincias para:', departmentName);
    
    const provinceSelect = document.getElementById('province');
    if (!provinceSelect) {
        console.error('15. NO SE ENCONTRÓ EL SELECT DE PROVINCIAS');
        return;
    }
    
    const department = ubigeoData.departments.find(dept => dept.name === departmentName);
    if (!department) {
        console.error('16. DEPARTAMENTO NO ENCONTRADO:', departmentName);
        return;
    }
    
    provinceSelect.innerHTML = '<option value="">Seleccione una provincia</option>';
    provinceSelect.disabled = false;
    
    department.provinces.forEach(province => {
        const option = document.createElement('option');
        option.value = province.name;
        option.textContent = province.name;
        provinceSelect.appendChild(option);
    });
    
    console.log('17. Provincias cargadas:', department.provinces.length);
    
    // Event listener para provincias
    provinceSelect.removeEventListener('change', provinceChangeHandler);
    provinceSelect.addEventListener('change', provinceChangeHandler);
}

function provinceChangeHandler() {
    console.log('18. CAMBIO EN PROVINCIA:', this.value);
    if (this.value) {
        const departmentSelect = document.getElementById('state');
        populateDistrictsDebug(departmentSelect.value, this.value);
    }
}

function populateDistrictsDebug(departmentName, provinceName) {
    console.log('19. Poblando distritos para:', departmentName, '>', provinceName);
    
    const districtSelect = document.getElementById('city');
    if (!districtSelect) {
        console.error('20. NO SE ENCONTRÓ EL SELECT DE DISTRITOS');
        return;
    }
    
    const department = ubigeoData.departments.find(dept => dept.name === departmentName);
    const province = department?.provinces.find(prov => prov.name === provinceName);
    
    if (!province) {
        console.error('21. PROVINCIA NO ENCONTRADA');
        return;
    }
    
    districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
    districtSelect.disabled = false;
    
    province.districts.forEach(district => {
        const option = document.createElement('option');
        option.value = district;
        option.textContent = district;
        districtSelect.appendChild(option);
    });
    
    console.log('22. Distritos cargados:', province.districts.length);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM READY - Iniciando ubigeo debug...');
    
    // Esperar un poco para que el DOM se establezca completamente
    setTimeout(() => {
        loadAndTestUbigeo();
    }, 500);
});

// También intentar inmediatamente si el DOM ya está listo
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    console.log('DOM YA LISTO - Iniciando ubigeo debug inmediatamente...');
    setTimeout(() => {
        loadAndTestUbigeo();
    }, 100);
}