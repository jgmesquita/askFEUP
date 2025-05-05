@extends('layouts.app')
@section('content')
<div class="lb" id="leaderboard">
    <h2>Leaderboard</h2>
    <p>This page has the leaderboard of the users with most likes!</p>
    <div class="filters">
        <div class="dropdown-timefilter dropdown" onclick="toggleDropdown(event)">
            <button class="icon-text filter">
                <i class="material-symbols-outlined">keyboard_arrow_down</i>
                <span>Past Month</span>
            </button>
            <div class="dropdown-content hidden">
                <ul>
                    <li class="time-selected" data-item-time='month' onclick="handleLeaderbordFilter(this)">Past Month</li>
                    <li class="time" data-item-time='week' onclick="handleLeaderbordFilter(this)">Past Week</li>
                    <li class="time" data-item-time='year' onclick="handleLeaderbordFilter(this)">Past Year</li>
                    <li class="time" data-item-time='all' onclick="handleLeaderbordFilter(this)">All Time</li>
                </ul>
            </div>
        </div>
    </div>
    <table>
        <thead class="lb_thead">
            <tr>
                <th> <div class="thead_rank">Rank </div></th>
                <th> <div class="thead_rank">User </div> </th>
                <th> <div class="thead_rank">Total Likes </div></th>
            
            </tr>
        </thead>
        <tbody>
            @include('partials.leaderboard-users', ['users' => $users])
        </tbody>
    </table>
</div>
@endsection