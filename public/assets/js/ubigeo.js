/**
 * Manejo de selección de ubicación geográfica (Departamento, Provincia, Distrito)
 * basado en datos de ubigeo del Perú
 */

let ubigeoData = null;

// Cargar datos de ubigeo al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando carga de ubigeo...');
    
    // Verificar que los elementos existan
    const departmentSelect = document.getElementById('state');
    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('city');
    
    if (!departmentSelect) {
        console.error('No se encontró el select de departamentos');
        return;
    }
    
    // Inicializar elementos
    if (provinceSelect) provinceSelect.disabled = true;
    if (districtSelect) districtSelect.disabled = true;
    
    loadUbigeoData();
    initializeLocationSelects();
});

/**
 * Cargar datos de ubigeo desde el archivo JSON
 */
async function loadUbigeoData() {
    try {
        // Mostrar indicador de carga
        showLoadingIndicator();
        
        const response = await fetch('assets/data/ubigeo_peru.json');
        if (!response.ok) {
            throw new Error('Error al cargar datos de ubigeo');
        }
        ubigeoData = await response.json();
        
        // Ocultar indicador de carga
        hideLoadingIndicator();
        
        populateDepartments();
    } catch (error) {
        console.error('Error cargando datos de ubigeo:', error);
        hideLoadingIndicator();
        showErrorMessage('Error al cargar datos de ubicación. Por favor, recarga la página.');
        // En caso de error, mantener los inputs de texto como fallback
        handleUbigeoError();
    }
}

/**
 * Inicializar event listeners para los selects de ubicación
 */
function initializeLocationSelects() {
    const departmentSelect = document.getElementById('state');
    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('city');

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            const selectedDepartment = this.value;
            console.log('Departamento seleccionado:', selectedDepartment);
            
            if (selectedDepartment) {
                populateProvinces(selectedDepartment);
            } else {
                clearProvinces();
            }
            clearDistricts();
        });
    }

    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const selectedDepartment = departmentSelect.value;
            const selectedProvince = this.value;
            console.log('Provincia seleccionada:', selectedProvince);
            
            if (selectedProvince) {
                populateDistricts(selectedDepartment, selectedProvince);
            } else {
                clearDistricts();
            }
        });
    }
}

/**
 * Poblar el select de departamentos
 */
function populateDepartments() {
    const departmentSelect = document.getElementById('state');
    if (!departmentSelect || !ubigeoData) {
        console.error('No se pudo encontrar el select de departamentos o los datos de ubigeo');
        return;
    }

    // Habilitar el select de departamentos
    departmentSelect.disabled = false;
    
    // Limpiar opciones existentes
    departmentSelect.innerHTML = '<option value="">Seleccione un departamento</option>';

    // Agregar departamentos
    ubigeoData.departments.forEach(department => {
        const option = document.createElement('option');
        option.value = department.name;
        option.textContent = department.name;
        departmentSelect.appendChild(option);
    });
    
    console.log('Departamentos cargados:', ubigeoData.departments.length);
}

/**
 * Poblar el select de provincias según el departamento seleccionado
 */
function populateProvinces(departmentName) {
    const provinceSelect = document.getElementById('province');
    if (!provinceSelect || !ubigeoData || !departmentName) {
        console.log('No se puede poblar provincias:', { provinceSelect: !!provinceSelect, ubigeoData: !!ubigeoData, departmentName });
        return;
    }

    // Limpiar opciones existentes
    provinceSelect.innerHTML = '<option value="">Seleccione una provincia</option>';
    
    // Encontrar el departamento seleccionado
    const department = ubigeoData.departments.find(dept => dept.name === departmentName);
    if (!department) {
        console.error('Departamento no encontrado:', departmentName);
        return;
    }

    // Agregar provincias
    department.provinces.forEach(province => {
        const option = document.createElement('option');
        option.value = province.name;
        option.textContent = province.name;
        provinceSelect.appendChild(option);
    });

    // Habilitar el select de provincias
    provinceSelect.disabled = false;
    console.log('Provincias cargadas para', departmentName, ':', department.provinces.length);
}

/**
 * Poblar el select de distritos según el departamento y provincia seleccionados
 */
function populateDistricts(departmentName, provinceName) {
    const districtSelect = document.getElementById('city');
    if (!districtSelect || !ubigeoData || !departmentName || !provinceName) {
        console.log('No se puede poblar distritos:', { districtSelect: !!districtSelect, ubigeoData: !!ubigeoData, departmentName, provinceName });
        return;
    }

    // Limpiar opciones existentes
    districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';

    // Encontrar el departamento y provincia seleccionados
    const department = ubigeoData.departments.find(dept => dept.name === departmentName);
    if (!department) {
        console.error('Departamento no encontrado:', departmentName);
        return;
    }

    const province = department.provinces.find(prov => prov.name === provinceName);
    if (!province) {
        console.error('Provincia no encontrada:', provinceName);
        return;
    }

    // Agregar distritos
    province.districts.forEach(district => {
        const option = document.createElement('option');
        option.value = district;
        option.textContent = district;
        districtSelect.appendChild(option);
    });

    // Habilitar el select de distritos
    districtSelect.disabled = false;
    console.log('Distritos cargados para', provinceName, ':', province.districts.length);
}

/**
 * Limpiar el select de provincias
 */
function clearProvinces() {
    const provinceSelect = document.getElementById('province');
    if (provinceSelect) {
        provinceSelect.innerHTML = '<option value="">Seleccione una provincia</option>';
        provinceSelect.disabled = true;
    }
}

/**
 * Limpiar el select de distritos
 */
function clearDistricts() {
    const districtSelect = document.getElementById('city');
    if (districtSelect) {
        districtSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
        districtSelect.disabled = true;
    }
    // No limpiar provincias aquí, se hace por separado
}

/**
 * Manejar errores de carga de ubigeo manteniendo inputs de texto
 */
function handleUbigeoError() {
    console.warn('Usando inputs de texto como fallback para ubicación');
    
    // Si no se pueden cargar los datos, mantener los inputs de texto
    const departmentElement = document.getElementById('state');
    const provinceElement = document.getElementById('province');
    const districtElement = document.getElementById('city');

    if (departmentElement && departmentElement.tagName === 'SELECT') {
        // Convertir select a input si es necesario
        const input = document.createElement('input');
        input.type = 'text';
        input.id = departmentElement.id;
        input.name = departmentElement.name;
        input.className = departmentElement.className;
        input.placeholder = 'Ingrese su departamento';
        input.required = departmentElement.required;
        departmentElement.parentNode.replaceChild(input, departmentElement);
    }
}

/**
 * Validar que se hayan seleccionado todos los campos de ubicación
 */
function validateLocationFields() {
    const department = document.getElementById('state').value;
    const province = document.getElementById('province').value;
    const district = document.getElementById('city').value;

    const errors = [];

    if (!department) {
        errors.push('Debe seleccionar un departamento');
        markFieldAsInvalid('state', 'Debe seleccionar un departamento');
    } else {
        markFieldAsValid('state');
    }
    
    if (!province) {
        errors.push('Debe seleccionar una provincia');
        markFieldAsInvalid('province', 'Debe seleccionar una provincia');
    } else {
        markFieldAsValid('province');
    }
    
    if (!district) {
        errors.push('Debe seleccionar un distrito');
        markFieldAsInvalid('city', 'Debe seleccionar un distrito');
    } else {
        markFieldAsValid('city');
    }

    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Marcar campo como válido
 */
function markFieldAsValid(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        // Remover mensaje de error previo
        const errorMsg = field.parentNode.querySelector('.invalid-feedback');
        if (errorMsg) {
            errorMsg.remove();
        }
    }
}

/**
 * Marcar campo como inválido
 */
function markFieldAsInvalid(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        
        // Remover mensaje de error previo
        const existingErrorMsg = field.parentNode.querySelector('.invalid-feedback');
        if (existingErrorMsg) {
            existingErrorMsg.remove();
        }
        
        // Agregar nuevo mensaje de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
}

/**
 * Mostrar indicador de carga
 */
function showLoadingIndicator() {
    const departmentSelect = document.getElementById('state');
    if (departmentSelect) {
        departmentSelect.innerHTML = '<option value="">Cargando departamentos...</option>';
        // No deshabilitar aquí, se habilitará en populateDepartments
    }
}

/**
 * Ocultar indicador de carga
 */
function hideLoadingIndicator() {
    // La función populateDepartments se encargará de restaurar el contenido
}

/**
 * Mostrar mensaje de error
 */
function showErrorMessage(message) {
    // Crear o actualizar mensaje de error global
    let errorContainer = document.getElementById('ubigeo-error');
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.id = 'ubigeo-error';
        errorContainer.className = 'alert alert-warning mt-2';
        
        const departmentField = document.getElementById('state');
        if (departmentField) {
            departmentField.parentNode.appendChild(errorContainer);
        }
    }
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
}

// Exportar funciones para uso externo si es necesario
window.ubigeoManager = {
    validateLocationFields,
    populateDepartments,
    clearProvinces,
    clearDistricts,
    markFieldAsValid,
    markFieldAsInvalid
};