@extends('layouts.app')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
@section('content')
    <section id="edit-profile">
        <h2>Edit Profile</h2>

        <div class="edit-container">
            <div class="edit-option name" onclick="togglePopup(this, 'edit-container')">
                <p>Display Name</p>
                <!--<i class="material-icons">edit</i> -->
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Name</h1>
                        <p>Update the name you want to be displayed to other users</p>
                        <form method="POST" action="{{ route('edit-profile.name', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="updateName">
                            @csrf
                            <div class="form-group">         
                                <input id="name" type="text" name="name" value="{{ Auth::user()->name }}" required autofocus placeholder="John Doe">  
                                <label for="name">Name<span class="mandatory">*</span></label>
                            </div>
                        </form>
                        <div class="button-group">
                            <button form="updateName" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option tagname" onclick="togglePopup(this, 'edit-container')">
                <p>Tagname</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Tagname</h1>
                        <p>Update the tagname of your profile. Make sure it isn't already being used by another user</p>
                        <form method="POST" action="{{ route('edit-profile.tagname', ['id' => Auth::id()]) }}" enctype="multipart/form-data" id="tagName">
                            @csrf
                            <div class="form-group">         
                                <input id="tagname" type="text" name="tagname" value="{{ Auth::user()->tagname }}" placeholder="john.doe" required autofocus>  
                                <label for="tagname">Tagname<span class="mandatory">*</span></label>
                            </div>    
                        </form>
                        <div class="button-group">
                            <button form="tagName" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option email" onclick="togglePopup(this, 'edit-container')">
                <p>Email</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Email</h1>
                        <p>Update the email associated with your profile</p>
                        <form method="POST" action="{{ route('edit-profile.email', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="email-form">
                            @csrf
                            <div class="form-group">         
                                <input id="old-email" type="email" name="old-email" placeholder="jdoe@email.com" required autofocus>  
                                <label for="old-email">Current Email<span class="mandatory">*</span></label>
                            </div>

                            <div class="form-group">         
                                <input id="new-email" type="email" name="new-email" placeholder="jdoe@email.com" required autofocus>  
                                <label for="new-email">Enter New Email<span class="mandatory">*</span></label>
                            </div>

                            <div class="form-group">         
                                <input id="confirm-email" type="email" name="confirm-email" placeholder="jdoe@email.com" required>  
                                <label for="confirm-email">Confirm New Email<span class="mandatory">*</span></label>
                            </div>                            
                        </form>
                        <div class="button-group">
                            <button form="email-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option password" onclick="togglePopup(this, 'edit-container')">
                <p>Password</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Password</h1>
                        <p>Update the password of your account</p>
                        <form method="POST" action="{{ route('edit-profile.password', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="password-form">
                            @csrf
                            <div class="form-group">         
                                <input id="old-password" type="password" name="old-password" required autofocus placeholder="">   
                                <label for="old-password">Current Password<span class="mandatory">*</span></label>
                            </div>  
                            <div class="form-group">         
                                <input id="new-password" type="password" name="new-password" required placeholder="">  
                                <label for="new-password">New Password<span class="mandatory">*</span></label>
                            </div>  
                            <div class="form-group">         
                                <input id="confirm-password" type="password" name="new-password_confirmation" required placeholder="">  
                                <label for="confirm-password">Confirm New Password<span class="mandatory">*</span></label>
                            </div>       
                        </form>
                        <div class="button-group">
                            <button form="password-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option age" onclick="togglePopup(this, 'edit-container')">
                <p>Age</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Age</h1>
                        <p>Update your age. You must be at least 18 years old</p>
                        <form method="POST" action="{{ route('edit-profile.age', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="age-form">
                            @csrf
                            <div class="form-group">         
                                <input id="age" type="number" name="age" value="{{ Auth::user()->age }}" min="18" placeholder="18" required autofocus>
                                <label for="age">Age<span class="mandatory">*</span></label>
                            </div>
                        </form>
                        <div class="button-group">
                            <button form="age-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
        
        <div class="edit-container">
            <div class="edit-option country" onclick="togglePopup(this, 'edit-container')">
                <p>Country</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Country</h1>
                        <p>Update the country you are from</p>
                        <form method="POST" action="{{ route('edit-profile.country', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="country-form">
                            @csrf
                            <div class="form-group">         
                                <input id="country" type="text" name="country" value="{{ Auth::user()->country }}" placeholder="Portugal" autofocus>  
                                <label for="country">Country</label>
                            </div>
                        </form>
                        <div class="button-group">
                            <button form="country-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option degree" onclick="togglePopup(this, 'edit-container')">
                <p>Degree</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Degree</h1>
                        <p>Change your degree</p>
                        <form method="POST" action="{{ route('edit-profile.degree', ['id' => Auth::id()]) }}" enctype="multipart/form-data" class="form-group" id="degree-form">
                            @csrf
                            <div class="form-group">         
                                <input id="degree" type="text" name="degree" value="{{ Auth::user()->degree }}" placeholder="Bachelor" autofocus>  
                                <label for="degree">Degree</label>
                            </div>
                        </form>
                        <div class="button-group">
                            <button form="degree-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="edit-option profilepic" onclick="togglePopup(this, 'edit-container')">
                <p>Profile Picture</p>
                <i class="material-symbols-outlined">arrow_forward_ios</i>
            </div>
            <div class="popup hidden">
                <div class="overlay" onclick="togglePopup(this, 'edit-container')"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h1>Profile Picture</h1>
                        <p>Change your profile picture</p>
                        <form method="POST" action="{{ route('edit-profile.profilepicture', ['id' => Auth::id()]) }}" enctype="multipart/form-data" id="profilepicture-form">
                            @csrf
                            <label for="profile-picture-input" class="file-upload-button" id="upload-button">
                                +
                            </label>
                            <input id="profile-picture-input" type="file" name="profile_picture" accept="image/*" style="display: none;" onchange="openCropPopup(event)">
                            <p id="error-message"></p>
                            
    
                        </form>
                                   

                        <div class="button-group">
                            <button form="profilepicture-form" type="submit">Update</button>
                            <button class="cancel" onclick="togglePopup(this, 'edit-container')">Cancel</button>
                        </div> 
                         <!-- Área do Cropper -->
                        
                    </div>
                </div>
                <div id="crop-popup" class="hidden">
                    <div class="overlay" ></div>
                        <div class="content">
                            <h1>Crop Your Image</h1>
                            <div id="crop-container">
                                <img id="crop-image" src="" alt="Image to crop" style="max-width: 100%; display: block;">
                            </div>
                            <div class="button-group">
                                <button id="crop-save" type="button" onclick="saveCroppedImage()">Save</button>
                                <button class="cancel" onclick="closeCropPopup()">Cancel</button>
                            </div>
                        </div>
                </div>        
            </div>
        </div>
        <button onclick="deleteUser(this)">Delete Account</button>  
    </section>
@endsection
