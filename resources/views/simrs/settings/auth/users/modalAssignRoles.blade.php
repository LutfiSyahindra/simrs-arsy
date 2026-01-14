<div class="modal fade" id="assignRolesModal" tabindex="-1" aria-labelledby="assignRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="assignRolesModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <form id="assignRolesForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Roles</label>
                        <select name="roles_id[]" id="rolesSelect" class="js-example-basic-multiple form-select"
                            multiple="multiple" data-width="100%"></select>
                    </div>
                    <input id="userssId" class="form-control" name="userssId" type="hidden">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="assignRoles" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
