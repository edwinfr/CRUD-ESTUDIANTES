<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; }
        .card { box-shadow: 0 4px 18px rgba(0,0,0,.06); border: none; }
        .table td, .table th { vertical-align: middle; }
        .pagination .page-link { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">Sistema de Estudiantes</h2>
                    <p class="text-muted mb-0">CRUD con PHP, MySQL, Bootstrap, jQuery y AJAX</p>
                </div>
                <button class="btn btn-primary" id="btnNuevo">Nuevo estudiante</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o grado">
                </div>
                <div class="col-md-3">
                    <select id="estadoFilter" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Reprobado">Reprobado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="gradoFilter" class="form-select">
                        <option value="">Todos los grados</option>
                        <option value="1°">1°</option>
                        <option value="2°">2°</option>
                        <option value="3°">3°</option>
                        <option value="4°">4°</option>
                        <option value="5°">5°</option>
                        <option value="6°">6°</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100" id="btnBuscar">Buscar</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Grado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Cargando estudiantes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center" id="pagination"></ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="studentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nuevo estudiante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="studentId" name="id">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="grado" class="form-label">Grado</label>
                            <input type="text" class="form-control" id="grado" name="grado" required>
                        </div>
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="Aprobado">Aprobado</option>
                                <option value="Reprobado">Reprobado</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let searchTimer;
        const studentModal = new bootstrap.Modal(document.getElementById('studentModal'));

        function loadStudents(page = 1) {
            currentPage = page;
            const search = $('#searchInput').val().trim();
            const estado = $('#estadoFilter').val();
            const grado = $('#gradoFilter').val();

            $('#studentsTableBody').html('<tr><td colspan="5" class="text-center text-muted">Buscando...</td></tr>');

            $.ajax({
                url: 'api.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'list',
                    page,
                    search,
                    estado,
                    grado
                }
            }).done(function (res) {
                if (!res || res.status !== 'success') {
                    $('#studentsTableBody').html(`<tr><td colspan="5" class="text-center text-danger">${(res && res.message) ? res.message : 'Error al cargar los estudiantes.'}</td></tr>`);
                    return;
                }

                const rows = Array.isArray(res.data) ? res.data : [];
                if (!rows.length) {
                    $('#studentsTableBody').html('<tr><td colspan="5" class="text-center text-muted">No hay estudiantes registrados.</td></tr>');
                    $('#pagination').html('');
                    return;
                }

                let html = '';
                rows.forEach((student, index) => {
                    html += `
                        <tr>
                            <td>${((page - 1) * 5) + index + 1}</td>
                            <td>${student.nombre}</td>
                            <td>${student.grado}</td>
                            <td><span class="badge ${student.estado === 'Aprobado' ? 'bg-success' : 'bg-danger'}">${student.estado}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-2 btn-edit" data-id="${student.id}">Editar</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${student.id}">Eliminar</button>
                            </td>
                        </tr>`;
                });
                $('#studentsTableBody').html(html);
                renderPagination({
                    page: res.page,
                    totalPages: res.total_pages
                });
            }).fail(function () {
                $('#studentsTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar los estudiantes.</td></tr>');
            });
        }

        function renderPagination(pagination) {
            const { page, totalPages } = pagination;
            let html = '';

            html += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" data-page="${page - 1}">Anterior</a></li>`;

            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" data-page="${i}">${i}</a></li>`;
            }

            html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" data-page="${page + 1}">Siguiente</a></li>`;

            $('#pagination').html(html);
        }

        $('#btnBuscar').on('click', function () {
            loadStudents(1);
        });

        $('#searchInput').on('keydown', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(searchTimer);
                loadStudents(1);
            }
        });

        $('#estadoFilter, #gradoFilter').on('change', function () {
            loadStudents(1);
        });

        $('#pagination').on('click', '.page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page >= 1) {
                loadStudents(page);
            }
        });

        $('#btnNuevo').on('click', function () {
            $('#studentForm')[0].reset();
            $('#studentId').val('');
            $('#modalTitle').text('Nuevo estudiante');
            studentModal.show();
        });

        $(document).on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            $.getJSON('api.php', { action: 'details', id }).done(function (res) {
                if (res.success && res.data) {
                    $('#studentId').val(res.data.id);
                    $('#nombre').val(res.data.nombre);
                    $('#grado').val(res.data.grado);
                    $('#estado').val(res.data.estado);
                    $('#modalTitle').text('Editar estudiante');
                    studentModal.show();
                }
            });
        });

        $(document).on('click', '.btn-delete', function () {
            const id = $(this).data('id');
            if (!confirm('¿Deseas eliminar este estudiante?')) {
                return;
            }

            $.ajax({
                url: 'api.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'delete', id }
            }).done(function (res) {
                if (res.success) {
                    loadStudents(currentPage);
                    alert(res.message);
                } else {
                    alert(res.message);
                }
            });
        });

        $('#studentForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();
            $.ajax({
                url: 'api.php',
                type: 'POST',
                dataType: 'json',
                data: formData + '&action=save'
            }).done(function (res) {
                if (res.success) {
                    studentModal.hide();
                    loadStudents(currentPage);
                    alert(res.message);
                } else {
                    alert(res.message);
                }
            });
        });

        $(function () {
            loadStudents(1);
        });
    </script>
</body>
</html>
