@extends('layouts.app')

@section('content')

<div class="contactUsPage">
<h2>Contacts</h2>
    <div class="contacts-blocks">
        <section class="socialMedia">
        <p class="background1">Check us out</p>
            <p>
                <i id="ins" class="fab fa-instagram"></i> 
                @Ins_askFEUP
            </p>

            <p>
                <i id="tw" class="fab fa-twitter"></i> 
                @Tw_askFEUP
            </p>

            <p>
                <i id="fb" class="fab fa-facebook"></i> 
                @Fb_askFEUP
            </p>
        </section>
        
        <section class="contact-email">
            <p class="background1">Email</p>
            <a href="mailto:askfeupteam@gmail.com">askfeupteam@gmail.com</a> 
        </section>
    </div>

    <!-- Contact Form -->
    <section class="contact-form">
    <h2>Better Together</h2>
        <p>Have a question or feedback? Please fill out the form below and we’ll get back to you as soon as possible!</p>

        <div>
            <form method="POST" action="/contact">
                @csrf

                <div class="form-group">         
                    <input id="name" type="text" name="name" placeholder="John Doe" required>
                    <label for="name">Name<span class="mandatory">*</span></label>
                </div>

                <div class="form-group">
                    <input id="email" type="email" name="email" placeholder="jdoe@email.com" required>
                    <label for="email">Email Address<span class="mandatory">*</span></label>
                </div>
                
                <div class="form-group">
                    <textarea id="message" name="message" rows="4" placeholder="Write message" required></textarea>
                    <label for="message">Your Message<span class="mandatory">*</span></label>
                </div>

                <div class="button-group">
                    <button type="submit">Send Message</button>
                </div>
            </form>
        </div>
    </section>
</div>

@endsection
