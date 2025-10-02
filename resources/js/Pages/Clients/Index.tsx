import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

interface Client {
    id: number;
    name: string;
    email: string;
    contact_number: string;
    client_identifier: string;
    referrer: string;
    active: boolean;
}

interface Props {
    clients: Client[];
}

export default function Index({ clients }: Props) {
    const [selectedClients, setSelectedClients] = useState<number[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 10;

    // Filter clients based on search term
    const filteredClients = useMemo(() => {
        if (!searchTerm) return clients;

        const searchLower = searchTerm.toLowerCase();
        return clients.filter(
            (client) =>
                client.name.toLowerCase().includes(searchLower) ||
                client.email.toLowerCase().includes(searchLower) ||
                client.client_identifier.toLowerCase().includes(searchLower) ||
                client.contact_number?.toLowerCase().includes(searchLower) ||
                client.referrer.toLowerCase().includes(searchLower),
        );
    }, [clients, searchTerm]);

    // Calculate pagination
    const totalPages = Math.ceil(filteredClients.length / itemsPerPage);
    const paginatedClients = useMemo(() => {
        const start = (currentPage - 1) * itemsPerPage;
        return filteredClients.slice(start, start + itemsPerPage);
    }, [filteredClients, currentPage]);

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        setCurrentPage(1); // Reset to first page when searching
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this client?')) {
            router.delete(route('clients.destroy', id), {
                onSuccess: () => {
                    toast.success('Client deleted successfully');
                },
            });
        }
    };

    const handleBulkDelete = () => {
        if (selectedClients.length === 0) {
            toast.error('Please select clients to delete');
            return;
        }

        if (confirm('Are you sure you want to delete the selected clients?')) {
            router.post(
                route('clients.bulk-destroy'),
                { ids: selectedClients },
                {
                    onSuccess: () => {
                        setSelectedClients([]);
                        toast.success('Clients deleted successfully');
                    },
                },
            );
        }
    };

    const toggleSelectAll = () => {
        if (selectedClients.length === paginatedClients.length) {
            setSelectedClients([]);
        } else {
            setSelectedClients(paginatedClients.map((client) => client.id));
        }
    };

    const toggleSelect = (id: number) => {
        if (selectedClients.includes(id)) {
            setSelectedClients(
                selectedClients.filter((clientId) => clientId !== id),
            );
        } else {
            setSelectedClients([...selectedClients, id]);
        }
    };

    const truncateText = (text: string, maxLength: number = 12) => {
        if (text.length <= maxLength) return text;
        return text.slice(0, maxLength) + '...';
    };

    // Generate pagination links
    const paginationLinks = useMemo(() => {
        const links = [];

        // Previous page
        links.push({
            url: currentPage > 1 ? '#' : null,
            label: '&laquo; Previous',
            active: false,
        });

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            links.push({
                url: '#',
                label: i.toString(),
                active: i === currentPage,
            });
        }

        // Next page
        links.push({
            url: currentPage < totalPages ? '#' : null,
            label: 'Next &raquo;',
            active: false,
        });

        return links;
    }, [currentPage, totalPages]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Manage Clients
                </h2>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2 className="text-2xl font-semibold">
                                    Clients
                                </h2>
                                <Button
                                    onClick={() =>
                                        router.visit(route('clients.create'))
                                    }
                                >
                                    New Client
                                </Button>
                            </div>

                            <div className="mb-6 flex gap-4">
                                <Input
                                    placeholder="Search clients..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
                                    }
                                    className="max-w-sm"
                                />
                                {selectedClients.length > 0 && (
                                    <Button
                                        variant="destructive"
                                        onClick={handleBulkDelete}
                                    >
                                        Delete Selected
                                    </Button>
                                )}
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[50px]">
                                                <Checkbox
                                                    checked={
                                                        selectedClients.length ===
                                                        paginatedClients.length
                                                    }
                                                    onCheckedChange={
                                                        toggleSelectAll
                                                    }
                                                />
                                            </TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>
                                                Contact Number
                                            </TableHead>
                                            <TableHead className="w-[200px] whitespace-nowrap">
                                                Client Identifier
                                            </TableHead>
                                            <TableHead>Referrer</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {paginatedClients.map((client) => (
                                            <TableRow key={client.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedClients.includes(
                                                            client.id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelect(
                                                                client.id,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {client.name}
                                                </TableCell>
                                                <TableCell>
                                                    {client.email}
                                                </TableCell>
                                                <TableCell>
                                                    {client.contact_number}
                                                </TableCell>
                                                <TableCell>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <div className="flex items-center gap-2">
                                                                    <span className="overflow-hidden text-ellipsis whitespace-nowrap">
                                                                        {truncateText(
                                                                            client.client_identifier,
                                                                        )}
                                                                    </span>
                                                                    {client
                                                                        .client_identifier
                                                                        .length >
                                                                        12 && (
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="h-6 w-6 p-0"
                                                                        >
                                                                            <svg
                                                                                className="h-4 w-4"
                                                                                fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke="currentColor"
                                                                            >
                                                                                <path
                                                                                    strokeLinecap="round"
                                                                                    strokeLinejoin="round"
                                                                                    strokeWidth={
                                                                                        2
                                                                                    }
                                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                                                />
                                                                                <path
                                                                                    strokeLinecap="round"
                                                                                    strokeLinejoin="round"
                                                                                    strokeWidth={
                                                                                        2
                                                                                    }
                                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                                                />
                                                                            </svg>
                                                                        </Button>
                                                                    )}
                                                                </div>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="max-w-xs break-all">
                                                                    {
                                                                        client.client_identifier
                                                                    }
                                                                </p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </TableCell>
                                                <TableCell>
                                                    {client.referrer}
                                                </TableCell>
                                                <TableCell>
                                                    <span
                                                        className={`rounded-full px-2 py-1 text-sm capitalize ${
                                                            client.active
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}
                                                    >
                                                        {client.active
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.visit(
                                                                    route(
                                                                        'clients.edit',
                                                                        client.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    client.id,
                                                                )
                                                            }
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            <div className="mt-4 flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {paginatedClients.length} of{' '}
                                    {filteredClients.length} results
                                </div>
                                <div className="flex gap-2">
                                    {paginationLinks.map((link, i) => (
                                        <Button
                                            key={i}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => {
                                                if (
                                                    link.label.includes(
                                                        'Previous',
                                                    )
                                                ) {
                                                    setCurrentPage((prev) =>
                                                        Math.max(1, prev - 1),
                                                    );
                                                } else if (
                                                    link.label.includes('Next')
                                                ) {
                                                    setCurrentPage((prev) =>
                                                        Math.min(
                                                            totalPages,
                                                            prev + 1,
                                                        ),
                                                    );
                                                } else {
                                                    setCurrentPage(
                                                        parseInt(link.label),
                                                    );
                                                }
                                            }}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
