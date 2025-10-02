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

interface ClientDetail {
    id: number;
    client_id: number;
    client: {
        name: string;
    };
    app_name: string;
    name: string;
    value: string;
    client_identifier: string;
}

interface Props {
    clientDetails: ClientDetail[];
}

export default function Index({ clientDetails }: Props) {
    const [selectedDetails, setSelectedDetails] = useState<number[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 10;

    // Filter client details based on search term
    const filteredDetails = useMemo(() => {
        if (!searchTerm) return clientDetails;

        const searchLower = searchTerm.toLowerCase();
        return clientDetails.filter(
            (detail) =>
                detail.client?.name.toLowerCase().includes(searchLower) ||
                detail.app_name.toLowerCase().includes(searchLower) ||
                detail.name.toLowerCase().includes(searchLower) ||
                detail.value.toLowerCase().includes(searchLower) ||
                detail.client_identifier.toLowerCase().includes(searchLower),
        );
    }, [clientDetails, searchTerm]);

    // Calculate pagination
    const totalPages = Math.ceil(filteredDetails.length / itemsPerPage);
    const paginatedDetails = useMemo(() => {
        const start = (currentPage - 1) * itemsPerPage;
        return filteredDetails.slice(start, start + itemsPerPage);
    }, [filteredDetails, currentPage]);

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        setCurrentPage(1); // Reset to first page when searching
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this client detail?')) {
            router.delete(route('clientdetails.destroy', id), {
                onSuccess: () => {
                    toast.success('Client detail deleted successfully');
                },
            });
        }
    };

    const handleBulkDelete = () => {
        if (selectedDetails.length === 0) {
            toast.error('Please select client details to delete');
            return;
        }

        if (
            confirm(
                'Are you sure you want to delete the selected client details?',
            )
        ) {
            router.post(
                route('clientdetails.bulk-destroy'),
                { ids: selectedDetails },
                {
                    onSuccess: () => {
                        setSelectedDetails([]);
                        toast.success('Client details deleted successfully');
                    },
                },
            );
        }
    };

    const toggleSelectAll = () => {
        if (selectedDetails.length === paginatedDetails.length) {
            setSelectedDetails([]);
        } else {
            setSelectedDetails(paginatedDetails.map((detail) => detail.id));
        }
    };

    const toggleSelect = (id: number) => {
        if (selectedDetails.includes(id)) {
            setSelectedDetails(
                selectedDetails.filter((detailId) => detailId !== id),
            );
        } else {
            setSelectedDetails([...selectedDetails, id]);
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
                    Manage Client Details
                </h2>
            }
        >
            <Head title="Client Details" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2 className="text-2xl font-semibold">
                                    Client Details
                                </h2>
                                <Button
                                    onClick={() =>
                                        router.visit(
                                            route('clientdetails.create'),
                                        )
                                    }
                                >
                                    New Client Detail
                                </Button>
                            </div>

                            <div className="mb-6 flex gap-4">
                                <Input
                                    placeholder="Search client details..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
                                    }
                                    className="max-w-sm"
                                />
                                {selectedDetails.length > 0 && (
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
                                                        selectedDetails.length ===
                                                        paginatedDetails.length
                                                    }
                                                    onCheckedChange={
                                                        toggleSelectAll
                                                    }
                                                />
                                            </TableHead>
                                            <TableHead>Client</TableHead>
                                            <TableHead>App Name</TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Value</TableHead>
                                            <TableHead className="w-[200px] whitespace-nowrap">
                                                Client Identifier
                                            </TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {paginatedDetails.map((detail) => (
                                            <TableRow key={detail.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedDetails.includes(
                                                            detail.id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelect(
                                                                detail.id,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {detail.client?.name}
                                                </TableCell>
                                                <TableCell>
                                                    {detail.app_name}
                                                </TableCell>
                                                <TableCell>
                                                    {detail.name}
                                                </TableCell>
                                                <TableCell>
                                                    {detail.value}
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
                                                                            detail.client_identifier,
                                                                        )}
                                                                    </span>
                                                                    {detail
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
                                                                        detail.client_identifier
                                                                    }
                                                                </p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.visit(
                                                                    route(
                                                                        'clientdetails.edit',
                                                                        detail.id,
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
                                                                    detail.id,
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
                                    Showing {paginatedDetails.length} of{' '}
                                    {filteredDetails.length} results
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
