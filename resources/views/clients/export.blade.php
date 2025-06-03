<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Liste des clients</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #eee;
        }
    </style>
</head>

<body>
    <h2>Liste des clients</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Statut</th>
                <th>Type</th>
                <th>Date de création</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->nom }}</td>
                    <td>{{ $client->prenom }}</td>
                    <td>{{ $client->telephone }}</td>
                    <td>{{ $client->adresse }}</td>
                    <td>{{ $client->statut }}</td>
                    <td>{{ $client->type }}</td>
                    <td>{{ $client->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>