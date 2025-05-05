@foreach($tags as $tag)
            <tr class="tag" data-item-id="{{ $tag->id }}">
                <td>
                    <div class="action-item" style="background-color:<?= $tag->color; ?>; color:<?= $tag->color_text ?>;">{{ $tag->name }}</div>
                </td>
                <td>
                    <div class="tag-color" style="background-color:<?= $tag->color; ?>;"></div>
                </td>
                <td>
                    <div class="dropdown options" onclick="toggleDropdown(event)">
                        <button><i class="material-icons">more_horiz</i></button>
                        <div class="dropdown-content hidden">
                            <ul>
                                <li class="icon-text" onclick="openEditTag(this)">
                                    <i class="material-symbols-outlined">edit</i>
                                    <span>Edit</span>
                                </li>
                                <li class="icon-text" onclick="deleteTag(this)">
                                    <i class="material-symbols-outlined">delete</i>
                                    <span>Delete</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach