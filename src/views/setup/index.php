<?php

declare(strict_types=1);
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Initial superadmin setup</h1>
                <p class="text-muted">This page is available only before the first superadmin account is created.</p>
                <form method="post" action="/setup">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required maxlength="100" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create superadmin</button>
                </form>
            </div>
        </div>
    </div>
</div>