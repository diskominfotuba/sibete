@extends('layouts.auth')
@section('content')
<section class="section">
   <div class="container mt-5">
     <div class="row">
       <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
         @if ($errors->any())
         <div class="alert alert-warning">Email atau Password salah!</div>
         @endif
         <div class="card card-primary mt-5">
           <div class="card-header"><h4>LOGIN SIBETE</h4></div>
           <div class="card-body">
             <form method="POST" action="/login" class="needs-validation" novalidate="">
               @csrf
               <div class="form-group">
                 <label for="email">Email</label>
                 <input id="email" type="email" value="{{ old('email') }}" class="form-control" name="email" required tabindex="1" required autofocus>
                 <div class="invalid-feedback">
                   Please fill in your email
                 </div>
               </div>

               <div class="form-group">
                 <div class="d-block">
                    <label for="password" class="control-label">Password</label>
                   <div class="float-right">
                     <a href="#" class="text-small">
                       Lupa Password?
                     </a>
                   </div>
                 </div>
                 <input id="password" type="password" class="form-control" name="password" required tabindex="2" required>
                 <div class="invalid-feedback">
                   please fill in your password
                 </div>
               </div>

               <div class="form-group">
                 <div class="custom-control custom-checkbox">
                   <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                   <label class="custom-control-label" for="remember-me">Biarkan saya tetap login</label>
                 </div>
               </div>

               <div class="form-group">
                <button id="btnLoading" class="btn btn-primary btn-block d-none" type="button" disabled>
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Loading...
                </button>
                 <button id="btnSubmit" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                   Login
                 </button>
               </div>

           </div>
         </div>
         <div class="mt-3 text-muted text-center">
           Belum punya akun? <a href="/register">Daftar</a>
         </div>
         <div class="simple-footer">
          Copyright © 2023 Inspektorat Kabupaten Tulang Bawang. Developed By <a href="https://api.whatsapp.com/send?phone={{ env('NO_DEV') }}">Umaedi KH</a>
         </div>
       </div>
     </div>
   </div>
 </section>
@endsection