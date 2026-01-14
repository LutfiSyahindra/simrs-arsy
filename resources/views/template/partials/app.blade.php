<!DOCTYPE html>
<!--
Template Name: NobleUI - HTML Bootstrap 5 Admin Dashboard Template
Author: NobleUI
Website: https://www.nobleui.com
Portfolio: https://themeforest.net/user/nobleui/portfolio
Contact: nobleui123@gmail.com
Purchase: https://1.envato.market/nobleui_admin
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">

    @include("template.partials.head")
    @stack("style")

    <body>
        <div class="main-wrapper">

            <!-- partial:partials/_sidebar.html -->
            @include("template.partials.sidebar")
            <!-- partial -->

            <div class="page-wrapper">

                <!-- partial:partials/_navbar.html -->
                @include("template.partials.navbar")
                <!-- partial -->

                <div class="page-content">

                    @yield("content")

                </div>

                <!-- partial:partials/_footer.html -->
                @include("template.partials.footer")
                <!-- partial -->

            </div>
        </div>

        @include("template.partials.script")
        @stack("scripts")

    </body>

</html>
