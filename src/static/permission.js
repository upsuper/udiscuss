var permissionMap = (function () {
    var result = {};
    $.all('option', $('#permission')).forEach(function (elem) {
        result[elem.value] = elem.textContent.trim();
    });
    return result;
})();

function updatePermisions() {
    $.all('td.permission[data-permission]').forEach(function (elem) {
        elem.innerHTML = permissionMap[elem.dataset.permission];
    });
}
updatePermisions();

$('#permissions').on('click', function (evt) {
    var $target = evt.target;
    if ($target.tagName.toLowerCase() != 'a')
        return;
    evt.preventDefault();
    
    var $manage = $target.parentNode,
        $tr = $target.parentNode.parentNode,
        $permission = $('.permission', $tr);
    var $token = $('#token_field');
    var tkName = $token.name,
        tkValue = $token.value;
    var data = null, callback;
    function checkTarget(c) {
        return $target.classList.contains(c);
    }
    function createLink(c, text) {
        var $a = $.create('a');
        $a.href = '#';
        $a.classList.add(c);
        $a.innerHTML = text;
        return $a;
    }
    function setPermission(permission) {
        $permission.dataset.permission = permission;
        delete $permission.dataset.originPermission;
        $manage.empty();
        $manage.appendChild(createLink('edit', 'Edit'));
        if (parseInt($tr.dataset.id) > 0)
            $manage.appendChild(createLink('remove', 'Remove'));
        updatePermisions();
    }
    if (checkTarget('add')) {
        var username = $('#username').value.trim();
        var permission = $('#permission').value;
        data = {action: 'add', username: username, permission: permission};
        callback = function (resp) {
            if (!resp.result) {
                alert('Failed: ' + resp.reason);
                return;
            }
            var user_id = resp.user_id;
            var found = false;
            $.all('tr[data-id]').forEach(function (elem) {
                if (elem.dataset.id == user_id) {
                    found = true;
                    $('.permission', elem).dataset.permission = permission;
                }
            });
            if (!found) {
                var $td;
                var $tr = $.create('tr');
                $tr.dataset.id = user_id;

                $td = $.create('td');
                $td.innerHTML = $.escape(username);
                $tr.appendChild($td);

                $td = $.create('td');
                $td.classList.add('permission');
                $td.dataset.permission = permission;
                $tr.appendChild($td);

                $td = $.create('td');
                $td.appendChild(createLink('edit', 'Edit'));
                $td.appendChild(createLink('remove', 'Remove'));
                $tr.appendChild($td);

                var $tbody = $('tbody', $('#permissions'));
                $tbody.insertBefore($tr, $('.new', $tbody));
            }

            $('#username').value = '';
            updatePermisions();
        };
    } else if (checkTarget('edit')) {
        var $select = $.create('select');
        $select.innerHTML = $('#permission').innerHTML;
        $select.value = $permission.dataset.permission;
        $permission.dataset.originPermission = $select.value;
        delete $permission.dataset.permission;
        $permission.empty();
        $permission.appendChild($select);
        
        $manage.empty();
        $manage.appendChild(createLink('save', 'Save'));
        $manage.appendChild(createLink('cancel', 'Cancel'));
    } else if (checkTarget('save')) {
        var userid = $tr.dataset.id;
        var permission = $('select', $permission).value;
        data = {action: 'set', user_id: userid, permission: permission};
        callback = function (resp) {
            if (!resp.result) {
                alert('Failed: ' + resp.reason);
                return;
            }
            setPermission(permission);
        };
    } else if (checkTarget('cancel')) {
        setPermission($permission.dataset.originPermission);
    } else if (checkTarget('remove')) {
        var userid = $tr.dataset.id;
        data = {action: 'remove', user_id: userid};
        callback = function (resp) {
            if (!resp.result) {
                alert('Failed: ' + resp.reason);
                return;
            }
            var $tbody = $('tbody', $('#permissions'));
            $tbody.removeChild($tr);
        };
    }

    if (data) {
        data[tkName] = tkValue;
        $.post($('#action_url').value, data, {
            type: 'json',
            onsuccess: callback
        });
    }
});
