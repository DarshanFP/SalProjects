<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<title>Login - SalProjects</title>

	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
	<!-- End fonts -->

	<!-- core:css -->
	<link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
	<!-- endinject -->

	<!-- Plugin css for this page -->
	<link rel="stylesheet" href="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.css') }}">
	<!-- End plugin css for this page -->

	<!-- inject:css -->
	<link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
	<link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
	<!-- endinject -->

	<!-- Layout styles -->
	<link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style.css') }}">
	<!-- End layout styles -->

	<!-- Custom styles for this page -->
	<style>
		.form-control {
			background-color: #2b2b2b;
			color: #ffffff;
		}
		.form-control::placeholder {
			color: #b0b0b0;
		}
		.form-check-label {
			color: #ffffff;
		}
		.text-white {
			color: #ffffff !important;
		}

		/* Autofill styles */
		input:-webkit-autofill {
			-webkit-box-shadow: 0 0 0 30px #2b2b2b inset !important;
			-webkit-text-fill-color: #ffffff !important;
		}
	</style>

	<link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}" />
</head>
<body>
	<div class="main-wrapper">
		<div class="page-wrapper full-page">
			<div class="page-content d-flex align-items-center justify-content-center">
				<div class="mx-0 row w-100 auth-page">
					<div class="mx-auto col-md-6 col-xl-4">
						<div class="card">
							<div class="px-4 py-5 auth-form-wrapper">
								<a href="#" class="mb-2 noble-ui-logo logo-light d-block">SAL <span>Projects</span></a>
								<h5 class="mb-4 text-muted fw-normal">Project Management System</h5>

								<!-- Session Status -->
								<x-auth-session-status class="mb-4" :status="session('status')" />

								<form method="POST" action="{{ route('login') }}">
									@csrf

									<!-- Login -->
									<div class="mb-3">
										<x-input-label for="login" :value="__('Login with Email / Name / Username / Phone')" />
										<x-text-input id="login" class="form-control" type="text" name="login" :value="old('login')" required autofocus />
									</div>

									<!-- Password -->
									<div class="mb-3">
										<x-input-label for="password" :value="__('Password')" />
										<x-text-input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" />
										<x-input-error :messages="$errors->get('password')" class="mt-2" />
									</div>

									<!-- Remember Me -->
									<div class="mb-3 form-check">
										<input type="checkbox" class="form-check-input" id="remember_me" name="remember">
										<label class="form-check-label" for="remember_me">{{ __('Remember me') }}</label>
									</div>

									<!-- Actions -->
									<div class="d-flex align-items-center justify-content-between">
										@if (Route::has('password.request'))
											<a href="{{ route('password.request') }}" class="text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
												{{ __('Forgot your password?') }}
											</a>
										@endif
										<x-primary-button class="text-white btn btn-primary">
											{{ __('Log in') }}
										</x-primary-button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- core:js -->
	<script src="{{ asset('backend/assets/vendors/core/core.js')}}"></script>
	<!-- endinject -->

	<!-- Plugin js for this page -->
	<script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js')}}"></script>
	<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js')}}"></script>
	<!-- End plugin js for this page -->

	<!-- inject:js -->
	<script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js')}}"></script>
	<script src="{{ asset('backend/assets/js/template.js')}}"></script>
	<!-- endinject -->

	<!-- Custom js for this page -->
	<script src="{{ asset('backend/assets/js/dashboard-dark.js')}}"></script>
	<!-- End custom js for this page -->
</body>
</html>
