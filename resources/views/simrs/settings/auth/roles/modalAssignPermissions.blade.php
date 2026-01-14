<div class="modal fade" id="assignPermissionsModal" tabindex="-1" aria-labelledby="assignPermissionsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="assignPermissionsModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <form id="assignPermissionsForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Permissions</label>
                        <select name="permissions_id[]" id="permissionsSelect"
                            class="js-example-basic-multiple form-select" multiple="multiple"
                            data-width="100%"></select>
                    </div>
                    <input id="rolessId" class="form-control" name="rolessId" type="hidden">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="assignPermissions" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
